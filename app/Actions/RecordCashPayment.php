<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Record a cash payment ("واصل"). It comes off the customer's whole
 * balance, not a particular item, so paying more than today's work also
 * pays down an old debt.
 */
class RecordCashPayment
{
    /**
     * @return bool false when this form was already recorded
     */
    public function handle(Customer $customer, string $amount, CarbonInterface $at, User $user, string $formToken): bool
    {
        try {
            $customer->transactions()->create([
                'type' => Transaction::CREDIT,
                'amount' => $amount,
                'method' => Transaction::CASH,
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
