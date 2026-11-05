<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * A debit (عليه) by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => Transaction::DEBIT,
            'category' => 'charging',
            'description' => 'جوال',
            'quantity' => 1,
            'unit_price' => '1.00',
            'amount' => '1.00',
            'occurred_at' => now(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * A debit of a given amount.
     */
    public function debit(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::DEBIT,
            'unit_price' => $amount,
            'amount' => $amount,
        ]);
    }

    /**
     * A cash payment of a given amount.
     */
    public function credit(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::CREDIT,
            'category' => null,
            'description' => null,
            'quantity' => null,
            'unit_price' => null,
            'amount' => $amount,
            'method' => Transaction::CASH,
        ]);
    }
}
