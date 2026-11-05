<?php

use App\Models\Customer;
use App\Models\PriceItem;
use App\Models\Transaction;

beforeEach(function () {
    $this->customer = Customer::factory()->create();
});

function record(Customer $customer, string $type, string $amount): Transaction
{
    $factory = Transaction::factory()->for($customer);

    return ($type === 'debit' ? $factory->debit($amount) : $factory->credit($amount))->create();
}

test('a customer with no movements has a zero balance', function () {
    expect($this->customer->balance())->toBe('0.00');
});

test('debits add up to what the customer owes', function () {
    record($this->customer, 'debit', '2.00');
    record($this->customer, 'debit', '5.00');

    expect($this->customer->balance())->toBe('7.00');
});

test('a partial payment lowers the balance', function () {
    record($this->customer, 'debit', '10.00');
    record($this->customer, 'credit', '4.00');

    expect($this->customer->balance())->toBe('6.00');
});

test('paying ahead leaves a negative balance (the customer is owed)', function () {
    record($this->customer, 'credit', '10.00');
    record($this->customer, 'debit', '3.00');

    expect($this->customer->balance())->toBe('-7.00');
});

test('paying exactly settles the account', function () {
    record($this->customer, 'debit', '1.50');
    record($this->customer, 'debit', '2.50');
    record($this->customer, 'credit', '4.00');

    expect($this->customer->balance())->toBe('0.00');
});

test('decimal amounts add up exactly', function () {
    record($this->customer, 'debit', '0.10');
    record($this->customer, 'debit', '0.20');

    expect($this->customer->balance())->toBe('0.30');
});

test('other customers movements do not count', function () {
    record(Customer::factory()->create(), 'debit', '50.00');
    record($this->customer, 'debit', '1.00');

    expect($this->customer->balance())->toBe('1.00');
});

test('a customer or item with movements can no longer be deleted', function () {
    $item = PriceItem::factory()->create();

    expect($this->customer->canBeDeleted())->toBeTrue()
        ->and($item->canBeDeleted())->toBeTrue();

    Transaction::factory()->for($this->customer)->create(['price_item_id' => $item->id]);

    expect($this->customer->canBeDeleted())->toBeFalse()
        ->and($item->canBeDeleted())->toBeFalse();
});
