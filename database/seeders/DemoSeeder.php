<?php

namespace Database\Seeders;

use App\Actions\AddOneTimeCustomer;
use App\Actions\PickUpFreezeItem;
use App\Actions\RecordOpeningBalance;
use App\Actions\RecordPayment;
use App\Actions\RecordWork;
use App\Actions\ReverseTransaction;
use App\Actions\StoreFreezeItem;
use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\PriceItem;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

/**
 * Made-up data to show the system (screenshots, anyone trying it locally):
 * two weeks of a charging point's work up to today, recorded through the
 * app's own actions so it follows the same rules as the real thing.
 *
 *   php artisan migrate:fresh --seed --seeder=DemoSeeder
 *
 * Sign in as "demo" / "demo-password". It never runs on the real copy: it
 * refuses in production, on the real database, and on any database that
 * already has records. A fixed seed gives the same data every time; the
 * dates follow today, so the demo always looks current.
 */
class DemoSeeder extends Seeder
{
    /** The real copy's database, never to be filled with made-up data. */
    public const REAL_DATABASES = ['nuqta_shahn'];

    public const OWNER_USERNAME = 'demo';

    public const OWNER_PASSWORD = 'demo-password';

    private const DAYS = 14;

    private Randomizer $random;

    private User $owner;

    private User $worker;

    /** @var Collection<string, PriceItem> */
    private Collection $items;

    /** @var Collection<int, PaymentAccount> */
    private Collection $accounts;

    public function run(): void
    {
        $this->refuseRealData();

        $this->random = new Randomizer(new Mt19937(2026));
        $today = CarbonImmutable::today();

        try {
            $this->people();
            $this->priceList();
            $this->paymentAccounts();
            $customers = $this->customers();

            $this->at($today->subDays(self::DAYS)->setTime(9, 0));
            $this->oldNotebookDebts($customers);

            for ($daysAgo = self::DAYS - 1; $daysAgo >= 0; $daysAgo--) {
                $this->workDay($today->subDays($daysAgo), $customers, isToday: $daysAgo === 0);
            }

            $this->paidAhead($customers, $today);
            $this->freezer($customers, $today);
            $this->oneTimeVisitor($today);
            $this->confirmOldTransfers($today);
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * Stop before touching anything that isn't a fresh, throwaway database.
     */
    private function refuseRealData(): void
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder does not run in production.');
        }

        if (in_array(basename($database), self::REAL_DATABASES, true)) {
            throw new RuntimeException("DemoSeeder does not run on the real database ({$database}).");
        }

        if (Transaction::exists() || Customer::where('is_walk_in', false)->exists()) {
            throw new RuntimeException('DemoSeeder only fills an empty database: run php artisan migrate:fresh --seed --seeder=DemoSeeder');
        }
    }

    private function people(): void
    {
        $this->owner = User::create(['name' => 'مصعب', 'username' => self::OWNER_USERNAME, 'password' => self::OWNER_PASSWORD, 'is_owner' => true]);
        $this->worker = User::create(['name' => 'سامر', 'username' => 'demo-worker', 'password' => self::OWNER_PASSWORD, 'is_owner' => false]);
    }

    private function priceList(): void
    {
        $list = [
            ['جوال مع شاحن', 'charging', '1.00'],
            ['جوال بدون شاحن', 'charging', '2.00'],
            ['باور', 'charging', '3.00'],
            ['بطارية', 'charging', null],
            ['لابتوب', 'charging', '3.00'],
            ['قنينة مي', 'cooling', '1.00'],
            ['كيكة', 'cooling', '2.00'],
            ['شراب', 'drink', '2.00'],
            ['قهوة', 'drink', '3.00'],
        ];

        $this->items = collect($list)->mapWithKeys(fn (array $row) => [
            $row[0] => PriceItem::create(['name' => $row[0], 'category' => $row[1], 'price' => $row[2]]),
        ]);

        // An item no longer offered: hidden from the day page, kept on the price list.
        PriceItem::create(['name' => 'راديو', 'category' => 'charging', 'price' => '1.00'])->toggleActive();
    }

    private function paymentAccounts(): void
    {
        $this->accounts = collect([
            PaymentAccount::create(['name' => 'بنك فلسطين', 'holder_name' => 'مصعب', 'number' => '1234567']),
            PaymentAccount::create(['name' => 'جوال باي', 'holder_name' => 'مصعب', 'number' => '0599000000']),
        ]);
    }

    /**
     * Regulars by nickname, the way the debt notebook names them.
     *
     * @return Collection<int, Customer>
     */
    private function customers(): Collection
    {
        $people = [
            ['أبو محمد الطويل', '0599000101', null],
            ['أبو سامي', '0599000102', null],
            ['أم خالد', '0599000103', 'تدفع آخر الشهر'],
            ['الأستاذ نضال', '0599000104', null],
            ['أبو العبد', null, null],
            ['يوسف الحلاق', '0599000106', 'محل الحلاقة على الزاوية'],
            ['أبو أحمد السائق', '0599000107', null],
            ['حمزة', '0599000108', null],
            ['أبو رامي', null, null],
            ['أم يوسف', '0599000110', null],
            ['محمود الطالب', '0599000111', 'لابتوب للجامعة'],
            ['أبو خليل', '0599000112', null],
            ['الحاج عمر', null, 'يدفع كل جمعة'],
            ['أبو زياد', '0599000114', null],
            ['عماد', '0599000115', null],
            ['أبو فادي', null, null],
            ['أم علي', '0599000117', null],
            ['سعيد', '0599000118', null],
            ['أبو مازن', '0599000119', null],
            ['رامي النجار', '0599000120', null],
        ];

        return collect($people)->map(fn (array $person) => Customer::create([
            'name' => $person[0],
            'phone' => $person[1] ? '970'.ltrim($person[1], '0') : null,
            'notes' => $person[2],
        ]));
    }

    /**
     * What some customers already owed in the debt notebook (one is owed by us).
     *
     * @param  Collection<int, Customer>  $customers
     */
    private function oldNotebookDebts(Collection $customers): void
    {
        $record = app(RecordOpeningBalance::class);

        foreach ([[0, '35.00', true], [2, '48.50', true], [4, '12.00', true], [6, '27.00', true], [12, '60.00', true], [9, '5.00', false]] as [$index, $amount, $owed]) {
            $record->handle($customers[$index], $amount, $owed, $this->owner, $this->token());
        }
    }

    /**
     * One day of work: a handful of walk-in sales paid on the spot, and
     * regulars who leave things to charge and pay on collection, later,
     * partly, or by transfer.
     *
     * @param  Collection<int, Customer>  $customers
     */
    private function workDay(CarbonImmutable $day, Collection $customers, bool $isToday): void
    {
        $recordWork = app(RecordWork::class);
        $recordPayment = app(RecordPayment::class);
        $walkIn = Customer::walkIn();

        // Today is still going: fewer people so far, and the clock stops at noon.
        $visits = $isToday ? 7 : $this->random->getInt(7, 12);
        $time = $day->setTime(8, $this->random->getInt(0, 40));
        $regulars = $this->pick($customers, $visits);

        foreach ($regulars as $customer) {
            $time = $time->addMinutes($this->random->getInt(15, $isToday ? 35 : 70));
            $user = $this->random->getInt(1, 4) === 1 ? $this->worker : $this->owner;

            // Now and then a quick sale to someone passing by.
            if ($this->random->getInt(1, 3) === 1) {
                $this->at($time);
                $recordWork->handle($walkIn, [$this->line($this->pickOne(['شراب', 'قنينة مي', 'قهوة']))], $time, true, $user, $this->token());
                $time = $time->addMinutes(5);
            }

            $this->at($time);
            $lines = $this->linesFor($customer);
            $recordWork->handle($customer, $lines, $time, false, $user, $this->token());
            $total = collect($lines)->reduce(fn (string $sum, array $line) => bcadd($sum, bcmul($line['unit_price'], (string) $line['quantity'], 2), 2), '0.00');

            // How they pay: on collection (cash or transfer), part of it, today's
            // work plus some of an old debt, or not today.
            $paidAt = $time->addMinutes($this->random->getInt(40, 150));
            if ($isToday && $paidAt->format('H:i') > '13:30') {
                continue;
            }

            $this->at($paidAt);
            match ($this->random->getInt(1, 10)) {
                1, 2, 3, 4 => $recordPayment->handle($customer, $total, $paidAt, $user, $this->token()),
                5 => $recordPayment->handle($customer, $total, $paidAt, $user, $this->token(), $this->accounts[$this->random->getInt(0, 1)], (string) $this->random->getInt(10000, 99999)),
                6 => $recordPayment->handle($customer, bcdiv($total, '2', 2) === '0.00' ? $total : bcdiv($total, '2', 2), $paidAt, $user, $this->token()),
                7 => $recordPayment->handle($customer, $this->withSomeOldDebt($customer, $total), $paidAt, $user, $this->token()),
                default => null,
            };
        }

        // A mistake, corrected the same day with its reason (never the walk-in's
        // sale: it was paid on the spot, so its balance must stay at zero).
        if (! $isToday && $this->random->getInt(1, 4) === 1) {
            $wrong = Transaction::debits()->dailyWork()
                ->where('customer_id', '!=', $walkIn->id)
                ->whereDate('occurred_at', $day)
                ->whereNull('reverses_id')
                ->latest('id')
                ->first();

            if ($wrong && $wrong->canBeReversed()) {
                $this->at($time->addMinutes(20));
                app(ReverseTransaction::class)->handle($wrong, 'سُجّل بالخطأ على الزبون الغلط', $this->owner, $this->token());
            }
        }
    }

    /**
     * A regular who settled up by transfer last night and sent a little
     * extra for the coming days: the debtors page has someone "in their
     * favour", and the transfers page one still to confirm.
     *
     * @param  Collection<int, Customer>  $customers
     */
    private function paidAhead(Collection $customers, CarbonImmutable $today): void
    {
        $customer = $customers->sortBy(fn (Customer $customer) => (float) $customer->balance())->first();
        $owed = max(0, (float) $customer->balance());
        $at = $today->subDay()->setTime(20, 10);

        $this->at($at);
        app(RecordPayment::class)->handle($customer, number_format($owed + 15, 2, '.', ''), $at, $this->owner, $this->token(), $this->accounts[1], '48213');
    }

    /**
     * Today's work plus a round part of what they owed before, never more
     * than their whole balance (the extra pays down the old debt).
     */
    private function withSomeOldDebt(Customer $customer, string $today): string
    {
        $older = bcsub($customer->balance(), $today, 2);

        if (bccomp($older, '0', 2) <= 0) {
            return $today;
        }

        $extra = min((float) $older, (float) $this->pickOne(['5', '10', '15', '20']));

        return bcadd($today, number_format($extra, 2, '.', ''), 2);
    }

    /**
     * Things in the freezer now, and a few collected this week.
     *
     * @param  Collection<int, Customer>  $customers
     */
    private function freezer(Collection $customers, CarbonImmutable $today): void
    {
        $store = app(StoreFreezeItem::class);
        $pickUp = app(PickUpFreezeItem::class);

        $waiting = [[3, 'دجاجة', 2, 9], [7, 'لحمة', null, 3], [10, 'أكل عرس', 4, 0]];
        foreach ($waiting as [$index, $what, $quantity, $daysAgo]) {
            $this->at($today->subDays($daysAgo)->setTime(10, 15));
            $store->handle($customers[$index], $what, $quantity, $today->subDays($daysAgo), $this->owner, $this->token());
        }

        $collected = [[1, 'سمك', null, 6, 2, '5.00', true], [5, 'دجاجة', 1, 5, 1, '4.00', false], [13, 'خبز', 3, 3, 1, '0', false]];
        foreach ($collected as [$index, $what, $quantity, $storedAgo, $pickedAgo, $price, $payNow]) {
            $this->at($today->subDays($storedAgo)->setTime(11, 0));
            $item = $store->handle($customers[$index], $what, $quantity, $today->subDays($storedAgo), $this->owner, $this->token());

            $this->at($today->subDays($pickedAgo)->setTime(18, 30));
            $pickUp->handle($item, $price, $payNow, $this->worker, $this->token());
        }
    }

    /**
     * Someone passing through who left a phone and pays when they collect it.
     */
    private function oneTimeVisitor(CarbonImmutable $today): void
    {
        $at = $today->setTime(11, 40);
        $this->at($at);

        $visitor = app(AddOneTimeCustomer::class)->handle('صاحب الجوال الأسود', '970599000199', $today);
        app(RecordWork::class)->handle($visitor, [$this->line('جوال بدون شاحن')], $at, false, $this->owner, $this->token());
    }

    /**
     * The owner has confirmed the older transfers; the last few are still waiting.
     */
    private function confirmOldTransfers(CarbonImmutable $today): void
    {
        Transaction::credits()
            ->where('method', Transaction::TRANSFER)
            ->whereNull('reverses_id')
            ->where('occurred_at', '<', $today->subDays(2))
            ->get()
            ->each(function (Transaction $transfer) {
                $this->at(CarbonImmutable::parse($transfer->occurred_at)->addHours(3));
                $transfer->verify();
            });
    }

    /**
     * What a regular leaves: mostly phones, sometimes a power bank, a
     * battery priced by its size, a laptop or something cooled.
     *
     * @return list<array{price_item_id: int|null, category: string, description: string, quantity: int, unit_price: string}>
     */
    private function linesFor(Customer $customer): array
    {
        $lines = [$this->line($this->pickOne(['جوال مع شاحن', 'جوال مع شاحن', 'جوال بدون شاحن', 'جوال بدون شاحن', 'باور', 'لابتوب']), $this->random->getInt(1, 5) === 1 ? 2 : 1)];

        if ($this->random->getInt(1, 3) === 1) {
            $lines[] = $this->line($this->pickOne(['شراب', 'كيكة', 'قنينة مي', 'قهوة']));
        }

        if ($this->random->getInt(1, 8) === 1) {
            $lines[] = $this->line('بطارية', 1, $this->pickOne(['3.00', '4.00', '5.00']));
        }

        return $lines;
    }

    /**
     * @return array{price_item_id: int|null, category: string, description: string, quantity: int, unit_price: string}
     */
    private function line(string $name, int $quantity = 1, ?string $price = null): array
    {
        $item = $this->items[$name];

        return [
            'price_item_id' => $item->id,
            'category' => $item->category,
            'description' => $item->name,
            'quantity' => $quantity,
            'unit_price' => $price ?? $item->price,
        ];
    }

    /**
     * @template T
     *
     * @param  Collection<int, T>  $from
     * @return Collection<int, T>
     */
    private function pick(Collection $from, int $count): Collection
    {
        return collect($this->random->pickArrayKeys($from->all(), min($count, $from->count())))
            ->map(fn (int $key) => $from[$key])
            ->pipe(fn (Collection $picked) => collect($this->random->shuffleArray($picked->all())));
    }

    /**
     * @template T
     *
     * @param  list<T>  $options
     * @return T
     */
    private function pickOne(array $options): mixed
    {
        return $options[$this->random->getInt(0, count($options) - 1)];
    }

    /**
     * Set the clock, so records made "now" by the actions land on the demo's moment.
     */
    private function at(CarbonImmutable $moment): void
    {
        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);
    }

    private function token(): string
    {
        return (string) Str::uuid();
    }
}
