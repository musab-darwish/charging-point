<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Record a payment ("واصل"): cash, or a transfer when the account it arrived
 * on is given. It comes off the customer's whole balance, not a particular
 * item, so paying more than today's work also pays down an old debt.
 *
 * A transfer counts straight away and waits to be confirmed ("تأكدت").
 */
class RecordPayment
{
    /**
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
    ): bool {
        try {
            $customer->transactions()->create([
                'type' => Transaction::CREDIT,
                'amount' => $amount,
                'method' => $account ? Transaction::TRANSFER : Transaction::CASH,
                'payment_account_id' => $account?->id,
                'reference' => $account ? $reference : null,
                'occurred_at' => $at,
                'form_token' => $formToken,
                'user_id' => $user->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
