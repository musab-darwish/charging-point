<?php

namespace App\Support;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Everything on one day's page, like a page of the daily notebook: the
 * customers seen that day (latest first), and the day's totals.
 */
final class DaySheet
{
    /** @var Collection<int, DayRow> */
    public readonly Collection $rows;

    /** The day's items added up. */
    public readonly string $work;

    /** The day's payments added up (including ones for old debts). */
    public readonly string $paid;

    /** What is left of the day's work across all customers. */
    public readonly string $unpaid;

    public function __construct(public readonly CarbonImmutable $day)
    {
        $transactions = Transaction::onDay($day)
            ->with(['customer', 'paymentAccount', 'user'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $balances = $this->balances($transactions->pluck('customer_id')->unique());

        $this->rows = $transactions
            ->groupBy('customer_id')
            ->map(fn (Collection $movements, int $customerId) => DayRow::from(
                $movements->first()->customer,
                $movements,
                $balances[$customerId] ?? '0.00',
            ))
            ->sortByDesc(fn (DayRow $row) => $row->lastAt)
            ->values();

        $this->work = $this->sum(fn (DayRow $row) => $row->work);
        $this->paid = $this->sum(fn (DayRow $row) => $row->paid);
        $this->unpaid = $this->sum(fn (DayRow $row) => $row->status->remaining);
    }

    /**
     * Each customer's whole balance, in one query.
     *
     * @param  Collection<int, int>  $customerIds
     * @return array<int, string>
     */
    private function balances(Collection $customerIds): array
    {
        if ($customerIds->isEmpty()) {
            return [];
        }

        return Transaction::query()
            ->selectRaw("customer_id, SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) AS balance")
            ->whereIn('customer_id', $customerIds)
            ->groupBy('customer_id')
            ->pluck('balance', 'customer_id')
            ->map(fn ($balance) => number_format((float) $balance, 2, '.', ''))
            ->all();
    }

    /**
     * @param  callable(DayRow): string  $amount
     */
    private function sum(callable $amount): string
    {
        return $this->rows->reduce(fn (string $sum, DayRow $row) => bcadd($sum, $amount($row), 2), '0.00');
    }
}
