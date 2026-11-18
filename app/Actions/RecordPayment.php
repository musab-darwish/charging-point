<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Record a payment ("واصل"): cash, or a transfer when the account it arrived
 * on is given. It comes off the customer's whole balance, not a particular
 * item, so paying more than today's work also pays down an old debt.
 *
 * A transfer counts straight away and waits to be confirmed ("تأكدت"),
 * unless it is recorded as already confirmed (the owner saw it arrive).
 */
class RecordPayment
{
    /**
     * @param  bool  $confirmed  a transfer already seen in the account (the caller checks who may say so)
     * @return bool false when this form was already recorded
     */
    public function handle(
        Customer $customer,
        string $amount,
        CarbonInterface $at,
        User $user,
        string $formToken,
        ?PaymentAccount $account = null,
        ?string $reference = null,
        bool $confirmed = false,
    ): bool {
        try {
            DB::transaction(function () use ($customer, $amount, $at, $user, $formToken, $account, $reference, $confirmed) {
                $payment = $customer->transactions()->create([
                    'type' => Transaction::CREDIT,
                    'amount' => $amount,
                    'method' => $account ? Transaction::TRANSFER : Transaction::CASH,
                    'payment_account_id' => $account?->id,
                    'reference' => $account ? $reference : null,
                    'occurred_at' => $at,
                    'form_token' => $formToken,
                    'user_id' => $user->id,
                ]);

                if ($account && $confirmed) {
                    $payment->verify();
                }
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
