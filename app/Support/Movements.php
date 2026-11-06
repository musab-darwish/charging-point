<?php

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * One day's movements sorted out the way a page shows them: the items and
 * payments that still count, and the ones that were cancelled. A
 * cancellation always lands on the same day as what it cancels, so the
 * pair simply drops out of the day's work and payments together.
 */
final class Movements
{
    /**
     * @param  Collection<int, Transaction>  $items  debits that still count
     * @param  Collection<int, Transaction>  $payments  credits that still count
     * @param  Collection<int, Transaction>  $cancelled  cancelled movements, each with its reversal loaded
     */
    private function __construct(
        public readonly Collection $items,
        public readonly Collection $payments,
        public readonly Collection $cancelled,
    ) {}

    /**
     * @param  Collection<int, Transaction>  $transactions  one customer's movements on one day
     */
    public static function from(Collection $transactions): self
    {
        $reversals = $transactions->filter->isReversal()->keyBy('reverses_id');

        $counted = $transactions->reject(fn (Transaction $movement) => $movement->isReversal() || $reversals->has($movement->id));

        $cancelled = $transactions
            ->filter(fn (Transaction $movement) => $reversals->has($movement->id))
            ->each(fn (Transaction $movement) => $movement->setRelation('reversal', $reversals->get($movement->id)))
            ->values();

        return new self(
            items: $counted->filter->isDebit()->values(),
            payments: $counted->reject->isDebit()->values(),
            cancelled: $cancelled,
        );
    }

    /**
     * Everything to list for the day, cancelled ones in their place, in order.
     *
     * @return Collection<int, Transaction>
     */
    public function entries(): Collection
    {
        return $this->items->concat($this->payments)->concat($this->cancelled)
            ->sortBy([['occurred_at', 'asc'], ['id', 'asc']])
            ->values();
    }

    public function work(): string
    {
        return $this->items->reduce(fn (string $sum, Transaction $item) => bcadd($sum, $item->amount, 2), '0.00');
    }

    public function paid(): string
    {
        return $this->payments->reduce(fn (string $sum, Transaction $payment) => bcadd($sum, $payment->amount, 2), '0.00');
    }
}
