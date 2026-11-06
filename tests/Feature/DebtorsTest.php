<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Debtors;

function moveMoney(Customer $customer, string $type, string $amount, array $attributes = []): Transaction
{
    $factory = Transaction::factory()->for($customer);

    return ($type === 'debit' ? $factory->debit($amount) : $factory->credit($amount))->create($attributes);
}

test('those who owe come highest balance first, and settled customers are left out', function () {
    $small = Customer::factory()->create(['name' => 'صغير']);
    $big = Customer::factory()->create(['name' => 'كبير']);
    $settled = Customer::factory()->create(['name' => 'مسكّر']);

    moveMoney($small, 'debit', '5.00');
    moveMoney($big, 'debit', '40.00');
    moveMoney($big, 'credit', '10.00');
    moveMoney($settled, 'debit', '3.00');
    moveMoney($settled, 'credit', '3.00');

    $debtors = new Debtors;

    expect($debtors->owing->map(fn ($row) => [$row->customer->name, $row->balance])->all())->toBe([['كبير', '30.00'], ['صغير', '5.00']])
        ->and($debtors->totalOwing)->toBe('35.00')
        ->and($debtors->ahead)->toBeEmpty();
});

test('those who paid ahead are listed apart', function () {
    $ahead = Customer::factory()->create(['name' => 'دافع زيادة']);
    moveMoney($ahead, 'credit', '8.00');
    moveMoney($ahead, 'debit', '2.00');

    $debtors = new Debtors;

    expect($debtors->owing)->toBeEmpty()
        ->and($debtors->ahead->sole()->balance)->toBe('-6.00')
        ->and($debtors->totalAhead)->toBe('6.00');
});

test('the last payment is the latest real one', function () {
    $customer = Customer::factory()->create();
    moveMoney($customer, 'debit', '50.00', ['occurred_at' => now()->subDays(20)]);
    moveMoney($customer, 'credit', '5.00', ['occurred_at' => now()->subDays(12)]);
    $cancelled = moveMoney($customer, 'credit', '9.00', ['occurred_at' => now()->subDays(2)]);
    moveMoney($customer, 'debit', '9.00', ['occurred_at' => $cancelled->occurred_at, 'reverses_id' => $cancelled->id]);

    $row = (new Debtors)->owing->sole();

    expect($row->lastPayment->amount)->toBe('5.00')
        ->and($row->daysSincePayment())->toBe(12);
});

test('a balance moved from the notebook is not a payment', function () {
    $customer = Customer::factory()->create();
    moveMoney($customer, 'debit', '20.00');
    moveMoney($customer, 'credit', '3.00', ['category' => Transaction::OPENING]);

    $row = (new Debtors)->owing->sole();

    expect($row->balance)->toBe('17.00')
        ->and($row->lastPayment)->toBeNull()
        ->and($row->daysSincePayment())->toBeNull();
});

test('the debtors page shows each one with the total', function () {
    $customer = Customer::factory()->create(['name' => 'أبو محمد']);
    moveMoney($customer, 'debit', '42.00');
    moveMoney($customer, 'credit', '2.00', ['occurred_at' => now()->subDays(3)]);

    $this->actingAs(User::factory()->create())->get('/debtors')
        ->assertOk()
        ->assertSeeInOrder(['40.00', 'أبو محمد', 'آخر دفعة', '2.00', 'قبل 3 أيام', '40.00']);
});
