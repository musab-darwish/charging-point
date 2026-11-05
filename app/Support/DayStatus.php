<?php

namespace App\Support;

/**
 * A customer's state for one day in today's work, worked out from that
 * day's items ($work) and that day's payments ($paid) only, not from the
 * whole balance. So a customer with an old debt who paid today's work in
 * full shows as "واصل" for today.
 */
final class DayStatus
{
    /** Today's work is fully paid: واصل */
    public const PAID = 'paid';

    /** Nothing paid today yet: لم يُدفع بعد */
    public const UNPAID = 'unpaid';

    /** Some of today's work is paid: متبقي كذا */
    public const PARTIAL = 'partial';

    /**
     * @param  string  $state  one of the constants above
     * @param  string  $remaining  what is left of today's work, e.g. "3.00" ("0.00" when paid)
     */
    private function __construct(
        public readonly string $state,
        public readonly string $remaining,
    ) {}

    /**
     * @param  string  $work  the day's items added up, e.g. "5.00"
     * @param  string  $paid  the day's payments added up, e.g. "2.00"
     */
    public static function from(string $work, string $paid): self
    {
        // Paid as much as today's work or more (the extra went to an old debt).
        if (bccomp($paid, $work, 2) >= 0) {
            return new self(self::PAID, '0.00');
        }

        // Nothing paid yet: all of today's work is left.
        if (bccomp($paid, '0', 2) === 0) {
            return new self(self::UNPAID, bcadd($work, '0', 2));
        }

        // Paid some of it.
        return new self(self::PARTIAL, bcsub($work, $paid, 2));
    }

    /**
     * The badge text shown beside the customer.
     */
    public function label(): string
    {
        return match ($this->state) {
            self::PAID => 'واصل',
            self::UNPAID => 'لم يُدفع بعد',
            self::PARTIAL => 'متبقي '.$this->remaining.' ₪',
        };
    }
}
