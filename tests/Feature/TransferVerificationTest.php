<?php

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
    $this->customer = Customer::factory()->create();

    Transaction::factory()->for($this->customer)->debit('10.00')->create();

    $this->transfer = Transaction::factory()->for($this->customer)->credit('6.00')->create([
        'method' => Transaction::TRANSFER,
        'payment_account_id' => PaymentAccount::factory()->create()->id,
    ]);
});

test('the owner confirms a transfer arrived, and the balance stays the same', function () {
    $this->actingAs($this->owner)
        ->from('/')
        ->patch(route('transfers.verify', $this->transfer))
        ->assertRedirect('/')
        ->assertSessionHas('status');

    expect($this->transfer->fresh()->isVerified())->toBeTrue()
        ->and($this->transfer->fresh()->amount)->toBe('6.00')
        ->and($this->customer->balance())->toBe('4.00');
});

test('confirming again keeps the first time', function () {
    $this->travelTo(now()->subHour());
    $this->transfer->verify();
    $first = $this->transfer->fresh()->verified_at;
    $this->travelBack();

    $this->actingAs($this->owner)->patch(route('transfers.verify', $this->transfer));

    expect($this->transfer->fresh()->verified_at->equalTo($first))->toBeTrue();
});

test('a confirmation pressed by mistake can be taken back', function () {
    $this->transfer->verify();

    $this->actingAs($this->owner)->delete(route('transfers.unverify', $this->transfer));

    expect($this->transfer->fresh()->isVerified())->toBeFalse()
        ->and($this->customer->balance())->toBe('4.00');
});

test('only the owner confirms transfers', function () {
    $worker = User::factory()->create();

    $this->actingAs($worker)->patch(route('transfers.verify', $this->transfer))->assertForbidden();
    $this->actingAs($worker)->delete(route('transfers.unverify', $this->transfer))->assertForbidden();

    expect($this->transfer->fresh()->isVerified())->toBeFalse();
});

test('cash and items have nothing to confirm', function () {
    $cash = Transaction::factory()->for($this->customer)->credit('1.00')->create();
    $item = $this->customer->transactions()->debits()->first();

    $this->actingAs($this->owner)->patch(route('transfers.verify', $cash))->assertNotFound();
    $this->actingAs($this->owner)->patch(route('transfers.verify', $item))->assertNotFound();

    expect($cash->fresh()->verified_at)->toBeNull();
});

test('the day page shows an unconfirmed transfer, with the button for the owner only', function () {
    $this->actingAs($this->owner)->get('/')
        ->assertSee('لم تتأكد')
        ->assertSee(route('transfers.verify', $this->transfer));

    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('لم تتأكد')
        ->assertDontSee(route('transfers.verify', $this->transfer));
});

test('confirming a transfer never moves the time it was paid', function () {
    $paidAt = $this->transfer->occurred_at->toDateTimeString();

    $this->travel(2)->hours();
    $this->actingAs($this->owner)->patch(route('transfers.verify', $this->transfer));
    $this->actingAs($this->owner)->delete(route('transfers.unverify', $this->transfer));

    expect($this->transfer->fresh()->occurred_at->toDateTimeString())->toBe($paidAt);
});
