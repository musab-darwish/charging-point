<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Record a customer's work: one debit per item, and when they pay on the
 * spot ("تسجيل وواصل") a cash credit for the total, all in one database
 * transaction. The form token makes a second submit of the same form a no-op.
 */
class RecordWork
{
    /**
     * @param  list<array{price_item_id: int|null, category: string, description: string, quantity: int, unit_price: string}>  $items
     * @return bool false when this form was already recorded
     */
    public function handle(Customer $customer, array $items, CarbonInterface $at, bool $payNow, User $user, string $formToken): bool
    {
        if (Transaction::where('form_token', $formToken)->exists()) {
            return false;
        }

        try {
            DB::transaction(function () use ($customer, $items, $at, $payNow, $user, $formToken) {
                $total = '0.00';

                foreach ($items as $index => $item) {
                    $amount = bcmul($item['unit_price'], (string) $item['quantity'], 2);
                    $total = bcadd($total, $amount, 2);

                    $customer->transactions()->create([
                        'type' => Transaction::DEBIT,
                        'category' => $item['category'],
                        'price_item_id' => $item['price_item_id'],
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $amount,
                        'occurred_at' => $at,
                        'form_token' => $index === 0 ? $formToken : null,
                        'user_id' => $user->id,
                    ]);
                }

                if ($payNow && bccomp($total, '0', 2) > 0) {
                    $customer->transactions()->create([
                        'type' => Transaction::CREDIT,
                        'amount' => $total,
                        'method' => Transaction::CASH,
                        'occurred_at' => $at,
                        'user_id' => $user->id,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            // The same form arrived twice at the same moment; the first one won.
            return false;
        }

        return true;
    }
}
