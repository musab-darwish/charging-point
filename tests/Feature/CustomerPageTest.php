<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create(['name' => 'أبو محمد']);
});

test('the customer page shows the balance and the statement by day', function () {
    Transaction::factory()->for($this->customer)->debit('2.00')->create(['description' => 'باور', 'occurred_at' => '2026-11-03 09:00']);
    Transaction::factory()->for($this->customer)->debit('1.00')->create(['description' => 'جوال', 'occurred_at' => '2026-11-03 10:00']);
    Transaction::factory()->for($this->customer)->credit('1.00')->create(['occurred_at' => '2026-11-04 10:00']);

    $this->actingAs($this->user)
        ->get(route('customers.show', $this->customer))
        ->assertOk()
        ->assertSee('أبو محمد')
        ->assertSee('باور + جوال')
        ->assertSee('3-11-2026')
        ->assertSee('عليه 2.00');
});

test('a customer with no movements has an empty statement', function () {
    $this->actingAs($this->user)
        ->get(route('customers.show', $this->customer))
        ->assertOk()
        ->assertSee('لا توجد حركات لهذا الزبون بعد');
});

test('the walk-in customer has no page', function () {
    $this->actingAs($this->user)
        ->get(route('customers.show', Customer::walkIn()))
        ->assertNotFound();
});

test('work recorded from the customer page goes back to it', function () {
    $this->actingAs($this->user)
        ->post(route('work.store'), [
            'date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'items' => [['description' => 'جوال', 'quantity' => 1, 'unit_price' => '1']],
            'form_token' => (string) Str::uuid(),
            'back_to_customer' => $this->customer->id,
        ])
        ->assertRedirect(route('customers.show', $this->customer));

    expect($this->customer->balance())->toBe('1.00');
});

test('a payment made from the customer page goes back to it', function () {
    Transaction::factory()->for($this->customer)->debit('5.00')->create();

    $this->actingAs($this->user)
        ->post(route('payments.store'), [
            'customer_id' => $this->customer->id,
            'amount' => '5',
            'date' => now()->format('Y-m-d'),
            'form_token' => (string) Str::uuid(),
            'back_to_customer' => $this->customer->id,
        ])
        ->assertRedirect(route('customers.show', $this->customer));

    expect($this->customer->balance())->toBe('0.00');
});

test('without it, recording still goes back to the day page', function () {
    $this->actingAs($this->user)
        ->post(route('work.store'), [
            'date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'items' => [['description' => 'جوال', 'quantity' => 1, 'unit_price' => '1']],
            'form_token' => (string) Str::uuid(),
        ])
        ->assertRedirect(route('day.today'));
});
