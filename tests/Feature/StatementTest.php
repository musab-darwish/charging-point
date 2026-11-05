<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Support\Statement;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
});

function movement(Customer $customer, string $type, string $amount, string $at, string $description = 'جوال', int $quantity = 1): Transaction
{
    $factory = Transaction::factory()->for($customer);
    $factory = $type === 'debit' ? $factory->debit($amount) : $factory->credit($amount);

    return $factory->create(array_filter([
        'occurred_at' => $at,
        'description' => $type === 'debit' ? $description : null,
        'quantity' => $type === 'debit' ? $quantity : null,
    ], fn ($value) => $value !== null));
}

test('a customer with no movements has an empty statement', function () {
    $statement = new Statement($this->customer);

    expect($statement->days)->toBeEmpty()
        ->and($statement->balance)->toBe('0.00');
});

test('items are grouped by the day they happened, newest day first', function () {
    movement($this->customer, 'debit', '2.00', '2026-11-01 09:00');
    movement($this->customer, 'debit', '1.00', '2026-11-01 18:30');
    movement($this->customer, 'debit', '3.00', '2026-11-03 10:00');

    $days = (new Statement($this->customer))->days;

    expect($days)->toHaveCount(2)
        ->and($days[0]->date->toDateString())->toBe('2026-11-03')
        ->and($days[0]->work)->toBe('3.00')
        ->and($days[1]->date->toDateString())->toBe('2026-11-01')
        ->and($days[1]->items)->toHaveCount(2)
        ->and($days[1]->work)->toBe('3.00');
});

test('each day shows the balance the account ended that day on', function () {
    // Like the notebook: (2)+(3) on the first day, then 6 more and a payment of 4, then paid off.
    movement($this->customer, 'debit', '2.00', '2026-11-01 09:00');
    movement($this->customer, 'debit', '3.00', '2026-11-01 12:00');
    movement($this->customer, 'debit', '6.00', '2026-11-02 10:00');
    movement($this->customer, 'credit', '4.00', '2026-11-02 17:00');
    movement($this->customer, 'credit', '7.00', '2026-11-04 11:00');

    $statement = new Statement($this->customer);

    expect($statement->days->pluck('balanceAfter')->all())->toBe(['0.00', '7.00', '5.00'])
        ->and($statement->balance)->toBe('0.00')
        ->and($statement->balance)->toBe($this->customer->balance());
});

test('payments stay on the day they were made', function () {
    movement($this->customer, 'debit', '5.00', '2026-11-01 09:00');
    movement($this->customer, 'credit', '2.00', '2026-11-01 20:00');

    $day = (new Statement($this->customer))->days->first();

    expect($day->payments)->toHaveCount(1)
        ->and($day->paid)->toBe('2.00')
        ->and($day->balanceAfter)->toBe('3.00');
});

test('a day with only a payment for an old debt has no items', function () {
    movement($this->customer, 'debit', '10.00', '2026-11-01 09:00');
    movement($this->customer, 'credit', '10.00', '2026-11-05 09:00');

    $day = (new Statement($this->customer))->days->first();

    expect($day->items)->toBeEmpty()
        ->and($day->summary())->toBe('')
        ->and($day->balanceAfter)->toBe('0.00');
});

test('paying ahead carries a credit into the next days', function () {
    movement($this->customer, 'credit', '10.00', '2026-11-01 09:00');
    movement($this->customer, 'debit', '3.00', '2026-11-02 09:00');

    expect((new Statement($this->customer))->days->pluck('balanceAfter')->all())->toBe(['-7.00', '-10.00']);
});

test('repeated items on a day are merged into one line', function () {
    movement($this->customer, 'debit', '5.00', '2026-11-03 09:00', 'باور');
    movement($this->customer, 'debit', '1.00', '2026-11-03 10:00', 'جوال');
    movement($this->customer, 'debit', '4.00', '2026-11-03 11:00', 'جوال', 2);
    movement($this->customer, 'debit', '1.00', '2026-11-03 12:00', 'شراب');

    $day = (new Statement($this->customer))->days->first();

    expect($day->summary())->toBe('باور + جوال ×3 + شراب')
        ->and($day->lines()[1])->toBe(['description' => 'جوال', 'quantity' => 3, 'amount' => '5.00']);
});

test('other customers movements are not on the statement', function () {
    movement(Customer::factory()->create(), 'debit', '50.00', '2026-11-01 09:00');
    movement($this->customer, 'debit', '1.00', '2026-11-01 09:00');

    $statement = new Statement($this->customer);

    expect($statement->days)->toHaveCount(1)
        ->and($statement->balance)->toBe('1.00');
});
