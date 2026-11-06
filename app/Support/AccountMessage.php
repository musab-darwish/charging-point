<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The message a customer gets about their account, to copy or send on
 * WhatsApp. It starts from their last payment (cash or transfer): its date
 * and amount, and what was left after it. Then what was recorded since,
 * added up (short) or day by day (full), and what is left now, with the
 * accounts to transfer to. Cancelled movements and their cancellations are
 * left out entirely.
 */
final class AccountMessage
{
    /** The last real payment: not cancelled, and not a balance moved over from the notebook. */
    public readonly ?Transaction $lastPayment;

    /** What was left right after the last payment; null when they never paid. */
    public readonly ?string $balanceAfterPayment;

    /** @var Collection<int, Transaction> what still counts, recorded after the last payment */
    public readonly Collection $since;

    /** What was recorded after the last payment, added up. */
    public readonly string $sinceTotal;

    /** The account now. */
    public readonly string $balance;

    /** @var Collection<int, PaymentAccount> */
    public readonly Collection $accounts;

    public function __construct(public readonly Customer $customer)
    {
        $transactions = $customer->transactions()->orderBy('occurred_at')->orderBy('id')->get();
        $movements = Movements::from($transactions);

        $counted = $movements->items->concat($movements->payments)
            ->sortBy([['occurred_at', 'asc'], ['id', 'asc']])
            ->values();

        $this->lastPayment = $movements->payments
            ->reject(fn (Transaction $payment) => $payment->category === Transaction::OPENING)
            ->sortBy([['occurred_at', 'asc'], ['id', 'asc']])
            ->last();

        $cut = $this->lastPayment ? $counted->search(fn (Transaction $movement) => $movement->is($this->lastPayment)) + 1 : 0;

        $this->balanceAfterPayment = $this->lastPayment ? self::net($counted->take($cut)) : null;
        $this->since = $counted->slice($cut)->values();
        $this->sinceTotal = self::net($this->since);
        $this->balance = bcadd($this->balanceAfterPayment ?? '0.00', $this->sinceTotal, 2);
        $this->accounts = PaymentAccount::active()->ordered()->get();
    }

    /**
     * The short message: the last payment, what was recorded since, and
     * what is left. The one to send by default.
     */
    public function short(): string
    {
        return $this->compose(withDays: false);
    }

    /**
     * The full statement: the same, with every day since the last payment.
     */
    public function full(): string
    {
        return $this->compose(withDays: true);
    }

    /**
     * The days since the last payment, each with what was recorded and its total.
     *
     * @return Collection<int, array{date: CarbonImmutable, summary: string, total: string}>
     */
    public function days(): Collection
    {
        return $this->since
            ->groupBy(fn (Transaction $movement) => $movement->occurred_at->toDateString())
            ->map(function (Collection $movements, string $date) {
                $summary = $movements
                    ->groupBy(fn (Transaction $movement) => $movement->isDebit() ? $movement->description : $movement->description.' (لك)')
                    ->map(function (Collection $same, string $description) {
                        $quantity = $same->sum(fn (Transaction $movement) => $movement->quantity ?? 1);

                        return $quantity > 1 ? "{$description} ×{$quantity}" : $description;
                    })
                    ->implode(' + ');

                return ['date' => CarbonImmutable::parse($date), 'summary' => $summary, 'total' => self::net($movements)];
            })
            ->values();
    }

    /**
     * A wa.me link that opens WhatsApp on the customer's number with the
     * text ready; null when they have no number.
     */
    public function whatsappUrl(string $text): ?string
    {
        return $this->customer->phone ? 'https://wa.me/'.$this->customer->phone.'?text='.rawurlencode($text) : null;
    }

    private function compose(bool $withDays): string
    {
        $lines = ['مرحباً '.$this->customer->name.'،', 'هذا كشف حسابك لدى '.config('app.name').':', ''];

        if ($this->lastPayment) {
            $lines[] = 'آخر دفعة منك: '.$this->lastPayment->amount.' ₪ يوم '.self::date($this->lastPayment->occurred_at).'، '.self::left($this->balanceAfterPayment, after: true);
        }

        if ($this->since->isNotEmpty()) {
            $lines[] = ($this->lastPayment ? 'سُجّل عليك بعدها: ' : 'سُجّل عليك: ').self::money($this->sinceTotal).'.';

            if ($withDays) {
                $lines[] = '';
                $lines[] = $this->lastPayment ? 'التفاصيل بعد آخر دفعة:' : 'التفاصيل:';

                foreach ($this->days() as $day) {
                    $lines[] = '• '.self::date($day['date']).': '.$day['summary'].' = '.self::money($day['total']);
                }
            }
        }

        $lines[] = '';
        $lines[] = self::left($this->balance, after: false);

        if (bccomp($this->balance, '0', 2) > 0 && $this->accounts->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'للتحويل:';

            foreach ($this->accounts as $account) {
                $lines[] = '• '.$account->name.': '.$account->number.' (باسم '.$account->holder_name.')';
            }
        }

        $lines[] = '';
        $lines[] = 'شكراً لك.';

        return implode("\n", $lines);
    }

    /**
     * Where a balance leaves the customer, as a sentence.
     */
    private static function left(string $balance, bool $after): string
    {
        $sign = bccomp($balance, '0', 2);
        $amount = ltrim($balance, '-').' ₪';

        if ($after) {
            return match ($sign) {
                1 => 'وبقي عليك بعدها '.$amount.'.',
                -1 => 'وبقي لك بعدها '.$amount.'.',
                default => 'وكان حسابك بعدها مسكّراً.',
            };
        }

        return match ($sign) {
            1 => 'المتبقي عليك الآن: '.$amount,
            -1 => 'لك عندنا رصيد: '.$amount,
            default => 'حسابك مسكّر، لا شيء عليك.',
        };
    }

    private static function date(mixed $date): string
    {
        return CarbonImmutable::parse($date)->translatedFormat('l j-n-Y');
    }

    private static function money(string $amount): string
    {
        return (bccomp($amount, '0', 2) < 0 ? '−'.ltrim($amount, '-') : $amount).' ₪';
    }

    /**
     * Debits minus credits.
     *
     * @param  Collection<int, Transaction>  $movements
     */
    private static function net(Collection $movements): string
    {
        return $movements->reduce(
            fn (string $sum, Transaction $movement) => $movement->isDebit() ? bcadd($sum, $movement->amount, 2) : bcsub($sum, $movement->amount, 2),
            '0.00',
        );
    }
}
