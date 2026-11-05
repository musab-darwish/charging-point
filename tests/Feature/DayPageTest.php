<?php

use App\Models\Customer;
use App\Models\PriceItem;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('today is the main screen and shows the day and its customers', function () {
    $customer = Customer::factory()->create(['name' => 'Sami Baker']);
    Transaction::factory()->for($customer)->debit('3.00')->create(['description' => 'Power bank']);
    PriceItem::factory()->create(['name' => 'Phone with charger']);

    $this->actingAs($this->user)->get('/')
        ->assertOk()
        ->assertSee('Sami Baker')
        ->assertSee('Power bank')
        ->assertSee('لم يُدفع بعد')
        ->assertSee('Phone with charger');
});

test('an earlier day shows only that day', function () {
    $customer = Customer::factory()->create(['name' => 'Sami Baker']);
    Transaction::factory()->for($customer)->debit('3.00')->create(['occurred_at' => now()->subDays(2), 'description' => 'Old power bank']);
    Transaction::factory()->for($customer)->debit('1.00')->create(['description' => 'Today phone']);

    $this->actingAs($this->user)->get('/day/'.now()->subDays(2)->format('Y-m-d'))
        ->assertOk()
        ->assertSee('Old power bank')
        ->assertDontSee('Today phone');
});

test('today and future dates go to the main screen, a wrong date is not found', function () {
    $this->actingAs($this->user)->get('/day/'.now()->format('Y-m-d'))->assertRedirect('/');
    $this->actingAs($this->user)->get('/day/'.now()->addDay()->format('Y-m-d'))->assertRedirect('/');
    $this->actingAs($this->user)->get('/day/2026-13-45')->assertNotFound();
});

test('the customer picker search returns customers with their balance', function () {
    $customer = Customer::factory()->create(['name' => 'Sami Baker']);
    Transaction::factory()->for($customer)->debit('4.00')->create();

    $this->actingAs($this->user)->getJson('/customers/search?q=Sami')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Sami Baker', 'balance' => '4.00'])
        ->assertJsonMissing(['name' => 'زبون عابر']);
});
