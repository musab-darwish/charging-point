<?php

namespace App\Actions;

use App\Models\FreezeItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hand a freezer item back. Only now is it charged, at the price written at
 * pickup (zero means free, with no charge at all). "استلام وواصل" also
 * records a cash payment of the same amount.
 */
class PickUpFreezeItem
{
    /**
     * @return bool false when the item was already picked up (a double submit,
     *              or two people pressing at once)
     */
    public function handle(FreezeItem $item, string $price, bool $payNow, User $user, string $formToken): bool
    {
        return DB::transaction(function () use ($item, $price, $payNow, $user, $formToken) {
            // Lock the row so a second pickup waits, then sees it is already done.
            $item = FreezeItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! $item->isInFreezer()) {
                return false;
            }

            $now = now();

            $item->forceFill(['picked_up_at' => $now, 'picked_up_by' => $user->id])->save();

            if (bccomp($price, '0', 2) <= 0) {
                return true;
            }

            $item->customer->transactions()->create([
                'type' => Transaction::DEBIT,
                'category' => 'freezing',
                'description' => 'تجميد: '.$item->label(),
                'quantity' => 1,
                'unit_price' => $price,
                'amount' => $price,
                'freeze_item_id' => $item->id,
                'occurred_at' => $now,
                'form_token' => $formToken,
                'user_id' => $user->id,
            ]);

            if ($payNow) {
                $item->customer->transactions()->create([
                    'type' => Transaction::CREDIT,
                    'amount' => $price,
                    'method' => Transaction::CASH,
                    'occurred_at' => $now,
                    'user_id' => $user->id,
                ]);
            }

            return true;
        });
    }
}
