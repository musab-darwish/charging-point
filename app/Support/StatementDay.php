<?php

namespace App\Support;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * One day on a customer's statement: what they brought, what they paid,
 * and what their whole account stood at when the day ended.
 */
final class StatementDay
{
    /**
     * @param  Collection<int, Transaction>  $items  the day's debits
     * @param  Collection<int, Transaction>  $payments  the day's credits
     */
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly Collection $items,
        public readonly Collection $payments,
        public readonly string $work,
        public readonly string $paid,
        public readonly string $balanceAfter,
    ) {}

    /**
     * @param  Collection<int, Transaction>  $transactions  one customer's movements on the day
     * @param  string  $balanceBefore  the account at the end of the previous day
     */
    public static function from(CarbonImmutable $date, Collection $transactions, string $balanceBefore): self
    {
        $items = $transactions->filter->isDebit()->values();
        $payments = $transactions->reject->isDebit()->values();

        $work = $items->reduce(fn (string $sum, Transaction $item) => bcadd($sum, $item->amount, 2), '0.00');
        $paid = $payments->reduce(fn (string $sum, Transaction $payment) => bcadd($sum, $payment->amount, 2), '0.00');

        return new self(
            date: $date,
            items: $items,
            payments: $payments,
            work: $work,
            paid: $paid,
            balanceAfter: bcsub(bcadd($balanceBefore, $work, 2), $paid, 2),
        );
    }

    /**
     * The day's items with repeats merged, in the order they first came:
     * two "جوال" lines become one line with quantity 2.
     *
     * @return Collection<int, array{description: string, quantity: int, amount: string}>
     */
    public function lines(): Collection
    {
        return $this->items
            ->groupBy('description')
            ->map(fn (Collection $same, string $description) => [
                'description' => $description,
                'quantity' => $same->sum(fn (Transaction $item) => $item->quantity ?? 1),
                'amount' => $same->reduce(fn (string $sum, Transaction $item) => bcadd($sum, $item->amount, 2), '0.00'),
            ])
            ->values();
    }

    /**
     * The day in one line, like the debt notebook: "باور + جوال ×2 + شراب".
     */
    public function summary(): string
    {
        return $this->lines()
            ->map(fn (array $line) => $line['quantity'] > 1 ? "{$line['description']} ×{$line['quantity']}" : $line['description'])
            ->implode(' + ');
    }
}
