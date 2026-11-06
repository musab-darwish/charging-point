<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Move a customer's line from the debt notebook into the system: one
 * movement for the whole amount, owed ("عليه", a debit) or in their favour
 * ("له", a credit), dated the day it is moved. Once per customer: a wrong
 * one is cancelled (step 10) and moved again.
 */
class RecordOpeningBalance
{
    public const DESCRIPTION = 'رصيد سابق من الدفتر';

    /**
     * @return Transaction|null null when the customer already has one, or
     *                          this form was already sent
     */
    public function handle(Customer $customer, string $amount, bool $owed, User $user, string $formToken): ?Transaction
    {
        try {
            return DB::transaction(function () use ($customer, $amount, $owed, $user, $formToken) {
                // Lock the customer so two moves at once can't both find none.
                Customer::whereKey($customer->id)->lockForUpdate()->first();

                if ($customer->openingBalance()) {
                    return null;
                }

                return $customer->transactions()->create([
                    'type' => $owed ? Transaction::DEBIT : Transaction::CREDIT,
                    'category' => Transaction::OPENING,
                    'description' => self::DESCRIPTION,
                    'amount' => $amount,
                    'occurred_at' => now(),
                    'form_token' => $formToken,
                    'user_id' => $user->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
