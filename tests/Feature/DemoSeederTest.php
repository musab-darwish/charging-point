<?php

use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\PaymentAccount;
use App\Models\PriceItem;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DaySheet;
use App\Support\Debtors;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

test('it fills an empty database with two weeks of work up to today', function () {
    $this->seed(DemoSeeder::class);

    $owner = User::where('username', DemoSeeder::OWNER_USERNAME)->sole();

    expect($owner->is_owner)->toBeTrue()
        ->and(Hash::check(DemoSeeder::OWNER_PASSWORD, $owner->password))->toBeTrue()
        ->and(Customer::permanent()->count())->toBe(20)
        ->and(Transaction::whereDate('occurred_at', today())->exists())->toBeTrue()
        ->and(Transaction::whereDate('occurred_at', today()->subDays(13))->exists())->toBeTrue()
        ->and((new DaySheet(CarbonImmutable::today()))->rows)->not->toBeEmpty();
});

test('the made-up data follows the accounting rules', function () {
    $this->seed(DemoSeeder::class);

    expect(Transaction::where('amount', '<=', 0)->exists())->toBeFalse()
        ->and(Customer::walkIn()->balance())->toBe('0.00')
        ->and(Transaction::whereNotNull('reverses_id')->count())->toBeGreaterThan(0)
        ->and(Transaction::where('category', Transaction::OPENING)->count())->toBe(6);

    // A reversal is always the opposite type and the same amount, on the original's moment.
    Transaction::whereNotNull('reverses_id')->with('reverses')->get()->each(function (Transaction $reversal) {
        expect($reversal->type)->not->toBe($reversal->reverses->type)
            ->and($reversal->amount)->toBe($reversal->reverses->amount)
            ->and($reversal->occurred_at->equalTo($reversal->reverses->occurred_at))->toBeTrue();
    });
});

test('it shows every part of the system', function () {
    $this->seed(DemoSeeder::class);
    $debtors = new Debtors;

    expect($debtors->owing)->not->toBeEmpty()
        ->and($debtors->ahead)->not->toBeEmpty()
        ->and(Transaction::awaitingConfirmation()->exists())->toBeTrue()
        ->and(Transaction::where('method', Transaction::TRANSFER)->whereNotNull('verified_at')->exists())->toBeTrue()
        ->and(FreezeItem::whereNull('picked_up_at')->count())->toBe(3)
        ->and(FreezeItem::whereNotNull('picked_up_at')->count())->toBe(3)
        ->and(Customer::oneTime()->count())->toBe(1);
});

test('it gives the same data every time', function () {
    // Who, what and when, by name (ids change from one run to the next).
    $snapshot = fn () => Transaction::with('customer')->orderBy('id')->get()
        ->map(fn (Transaction $t) => [$t->customer->name, $t->type, $t->amount, $t->occurred_at->format('Y-m-d H:i')])
        ->all();

    $this->seed(DemoSeeder::class);
    $first = $snapshot();

    // Empty it again (reversals first, they point at what they cancel).
    Transaction::whereNotNull('reverses_id')->delete();
    Transaction::query()->delete();
    FreezeItem::query()->delete();
    Customer::where('is_walk_in', false)->delete();
    PaymentAccount::query()->delete();
    PriceItem::query()->delete();
    User::query()->delete();

    $this->seed(DemoSeeder::class);

    expect($snapshot())->toBe($first);
});

test('it refuses to run in production', function () {
    app()->instance('env', 'production');

    // Called directly: db:seed itself would first ask "are you sure" in production.
    expect(fn () => app(DemoSeeder::class)->run())->toThrow(RuntimeException::class, 'production');
    expect(User::count())->toBe(0);
});

test('it refuses to run on the real database', function () {
    $connection = config('database.default');
    $original = config("database.connections.{$connection}.database");
    config(["database.connections.{$connection}.database" => 'nuqta_shahn']);

    try {
        expect(fn () => $this->seed(DemoSeeder::class))->toThrow(RuntimeException::class, 'real database');
    } finally {
        config(["database.connections.{$connection}.database" => $original]);
    }

    expect(User::count())->toBe(0);
});

test('it refuses a database that already has records', function () {
    Customer::factory()->create();

    expect(fn () => $this->seed(DemoSeeder::class))->toThrow(RuntimeException::class, 'empty database');
    expect(User::where('username', DemoSeeder::OWNER_USERNAME)->exists())->toBeFalse();
});
