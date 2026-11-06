<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Cancel a movement entered by mistake. Nothing is deleted or changed: an
 * opposite movement of the same amount is added, pointing back at it
 * (reverses_id), with the reason. It lands on the same day as the original,
 * so that day's page and totals are corrected; who did it and when stay on
 * the reversal itself.
 *
 * A freezer pickup charge that is cancelled puts the item back in the freezer.
 */
class ReverseTransaction
{
    /**
     * @return bool false when it can't be cancelled (already cancelled, a
     *              cancellation itself, or this form was already sent)
     */
    public function handle(Transaction $transaction, string $reason, User $user, string $formToken): bool
    {
        try {
            return DB::transaction(function () use ($transaction, $reason, $user, $formToken) {
                // Lock the row so two cancellations at once can't both pass the check.
                $original = Transaction::whereKey($transaction->id)->lockForUpdate()->with('reversal')->firstOrFail();

                if (! $original->canBeReversed()) {
                    return false;
                }

                $original->customer->transactions()->create([
                    'type' => $original->isDebit() ? Transaction::CREDIT : Transaction::DEBIT,
                    'category' => $original->category,
                    'description' => $reason,
                    'amount' => $original->amount,
                    'occurred_at' => $original->occurred_at,
                    'reverses_id' => $original->id,
                    'freeze_item_id' => $original->freeze_item_id,
                    'form_token' => $formToken,
                    'user_id' => $user->id,
                ]);

                if ($original->isDebit() && $original->freeze_item_id) {
                    $original->freezeItem->forceFill(['picked_up_at' => null, 'picked_up_by' => null])->save();
                }

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            // The same form sent twice, or the database's own guard (reverses_id is unique).
            return false;
        }
    }
}
