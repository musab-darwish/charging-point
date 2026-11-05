<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * One customer's line on a day's page: what they brought, what they paid,
 * and where that leaves today (DayStatus) and their whole account (balance).
 */
final class DayRow
{
    /**
     * @param  Collection<int, Transaction>  $items  the day's debits
     * @param  Collection<int, Transaction>  $payments  the day's credits
     */
    public function __construct(
        public readonly Customer $customer,
        public readonly Collection $items,
        public readonly Collection $payments,
        public readonly string $work,
        public readonly string $paid,
        public readonly DayStatus $status,
        public readonly string $balance,
        public readonly CarbonInterface $lastAt,
    ) {}

    /**
     * @param  Collection<int, Transaction>  $transactions  one customer's movements on the day
     */
    public static function from(Customer $customer, Collection $transactions, string $balance): self
    {
        $items = $transactions->filter->isDebit()->values();
        $payments = $transactions->reject->isDebit()->values();

        $work = $items->reduce(fn (string $sum, Transaction $item) => bcadd($sum, $item->amount, 2), '0.00');
        $paid = $payments->reduce(fn (string $sum, Transaction $payment) => bcadd($sum, $payment->amount, 2), '0.00');

        return new self(
            customer: $customer,
            items: $items,
            payments: $payments,
            work: $work,
            paid: $paid,
            status: DayStatus::from($work, $paid),
            balance: $balance,
            lastAt: $transactions->max('occurred_at'),
        );
    }
}
