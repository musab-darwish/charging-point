<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;

class WorkDay
{
    /**
     * The moment to record something on a day: now when it's today, or the
     * current time of day on an earlier date (late entries from the notebook).
     */
    public static function momentOn(CarbonInterface $day): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return $day->isSameDay($now) ? $now : CarbonImmutable::parse($day)->setTimeFrom($now);
    }

    /**
     * The address of a day's page: "/" for today, "/day/2026-11-03" otherwise.
     */
    public static function url(CarbonInterface $day): string
    {
        return $day->isToday() ? route('day.today') : route('day.show', $day->format('Y-m-d'));
    }

    /**
     * Go back to a day's page after recording something on it.
     */
    public static function redirect(CarbonInterface $day): RedirectResponse
    {
        return redirect()->to(self::url($day));
    }

    /**
     * Go back to where a form was opened: the customer's page when it came
     * from there (back_to_customer), otherwise the day's page.
     */
    public static function redirectBack(CarbonInterface $day, mixed $customerId): RedirectResponse
    {
        return $customerId ? redirect()->route('customers.show', (int) $customerId) : self::redirect($day);
    }
}
