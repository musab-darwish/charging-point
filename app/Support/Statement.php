<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * A customer's statement, grouped by day like the debt notebook's line
 * (2)+(3)+(6), with the running balance after each day. Newest day first.
 */
final class Statement
{
    /** @var Collection<int, StatementDay> */
    public readonly Collection $days;

    /** The account now: the balance after the latest day. */
    public readonly string $balance;

    public function __construct(public readonly Customer $customer)
    {
        $transactions = $customer->transactions()
            ->with(['paymentAccount', 'user'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        // Walk the days oldest first, so each one starts from the balance the one before ended on.
        $balance = '0.00';
        $days = collect();

        foreach ($transactions->groupBy(fn (Transaction $transaction) => $transaction->occurred_at->toDateString()) as $date => $movements) {
            $day = StatementDay::from(CarbonImmutable::parse($date), $movements, $balance);
            $balance = $day->balanceAfter;
            $days->push($day);
        }

        $this->days = $days->reverse()->values();
        $this->balance = $balance;
    }
}
