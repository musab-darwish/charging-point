<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Who owes (highest first) and who has money with us, with the last time
 * each one paid. All balances come from one grouped query, not one per
 * customer.
 */
final class Debtors
{
    /** @var Collection<int, DebtorRow> customers who owe, highest balance first */
    public readonly Collection $owing;

    /** @var Collection<int, DebtorRow> customers who paid ahead, most in their favour first */
    public readonly Collection $ahead;

    /** What everyone owes, added up. */
    public readonly string $totalOwing;

    /** What everyone paid ahead, added up (as a positive amount). */
    public readonly string $totalAhead;

    public function __construct()
    {
        $balances = Transaction::query()
            ->selectRaw("customer_id, SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) AS balance")
            ->groupBy('customer_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->customer_id => number_format((float) $row->balance, 2, '.', '')])
            ->reject(fn (string $balance) => bccomp($balance, '0', 2) === 0);

        $customers = Customer::regular()->whereIn('id', $balances->keys())->get()->keyBy('id');
        $lastPayments = $this->lastPayments($balances->keys());

        $rows = $customers->map(fn (Customer $customer) => new DebtorRow(
            customer: $customer,
            balance: $balances[$customer->id],
            lastPayment: $lastPayments->get($customer->id),
        ));

        $this->owing = $rows
            ->filter(fn (DebtorRow $row) => bccomp($row->balance, '0', 2) > 0)
            ->sort(fn (DebtorRow $a, DebtorRow $b) => bccomp($b->balance, $a->balance, 2) ?: strcmp($a->customer->name, $b->customer->name))
            ->values();

        $this->ahead = $rows
            ->filter(fn (DebtorRow $row) => bccomp($row->balance, '0', 2) < 0)
            ->sort(fn (DebtorRow $a, DebtorRow $b) => bccomp($a->balance, $b->balance, 2) ?: strcmp($a->customer->name, $b->customer->name))
            ->values();

        $this->totalOwing = $this->owing->reduce(fn (string $sum, DebtorRow $row) => bcadd($sum, $row->balance, 2), '0.00');
        $this->totalAhead = ltrim($this->ahead->reduce(fn (string $sum, DebtorRow $row) => bcadd($sum, $row->balance, 2), '0.00'), '-');
    }

    /**
     * Each customer's latest real payment: not one that was cancelled (or a
     * cancellation), and not a balance moved over from the notebook.
     *
     * @param  Collection<int, int>  $customerIds
     * @return Collection<int, Transaction>
     */
    private function lastPayments(Collection $customerIds): Collection
    {
        return Transaction::credits()
            ->dailyWork()
            ->whereIn('customer_id', $customerIds)
            ->whereNull('reverses_id')
            ->whereDoesntHave('reversal')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->unique('customer_id')
            ->keyBy('customer_id');
    }
}
