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
     * @param  Collection<int, Transaction>  $items  the day's debits that still count
     * @param  Collection<int, Transaction>  $payments  the day's credits that still count
     * @param  Collection<int, Transaction>  $cancelled  movements cancelled that day, with their reversal
     */
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly Collection $items,
        public readonly Collection $payments,
        public readonly Collection $cancelled,
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
        // A cancelled movement and its cancellation drop out together, so the balance is unchanged by them.
        $movements = Movements::from($transactions);
        $work = $movements->work();
        $paid = $movements->paid();

        return new self(
            date: $date,
            items: $movements->items,
            payments: $movements->payments,
            cancelled: $movements->cancelled,
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
     * Every movement to list in the day's details, cancelled ones in their place.
     *
     * @return Collection<int, Transaction>
     */
    public function entries(): Collection
    {
        return $this->items->concat($this->payments)->concat($this->cancelled)
            ->sortBy([['occurred_at', 'asc'], ['id', 'asc']])
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
