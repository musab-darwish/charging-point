<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Transaction;
use Carbon\CarbonImmutable;

/**
 * One customer on the debtors list: their balance and the last time they paid.
 */
final class DebtorRow
{
    public function __construct(
        public readonly Customer $customer,
        public readonly string $balance,
        public readonly ?Transaction $lastPayment,
    ) {}

    /**
     * Whole days since the last payment, by date (today is 0); null if they never paid.
     */
    public function daysSincePayment(): ?int
    {
        if (! $this->lastPayment) {
            return null;
        }

        return (int) CarbonImmutable::parse($this->lastPayment->occurred_at)->startOfDay()->diffInDays(CarbonImmutable::today());
    }
}
