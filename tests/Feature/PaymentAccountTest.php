<?php

use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;

test('only active accounts are offered', function () {
    $active = PaymentAccount::factory()->create();
    PaymentAccount::factory()->inactive()->create();

    expect(PaymentAccount::active()->pluck('id')->all())->toBe([$active->id]);
});

test('an account can be hidden and shown again', function () {
    $account = PaymentAccount::factory()->create();

    $account->toggleActive();
    expect($account->fresh()->is_active)->toBeFalse();

    $account->toggleActive();
    expect($account->fresh()->is_active)->toBeTrue();
});

test('an account with a transfer on it can no longer be deleted', function () {
    $account = PaymentAccount::factory()->create();

    expect($account->canBeDeleted())->toBeTrue();

    Transaction::factory()->credit('5.00')->create(['method' => Transaction::TRANSFER, 'payment_account_id' => $account->id]);

    expect($account->canBeDeleted())->toBeFalse()
        ->and(fn () => $account->delete())->toThrow(QueryException::class);
});

test('the same number cannot be added twice on one app', function () {
    PaymentAccount::factory()->create(['name' => 'جوال باي', 'number' => '0599123456']);

    expect(fn () => PaymentAccount::factory()->create(['name' => 'جوال باي', 'number' => '0599123456']))
        ->toThrow(QueryException::class);

    // The same number on another app is fine.
    expect(PaymentAccount::factory()->create(['name' => 'بال باي', 'number' => '0599123456'])->exists)->toBeTrue();
});

test('only the owner manages accounts and confirms transfers', function () {
    $owner = User::factory()->create(['is_owner' => true]);
    $worker = User::factory()->create();

    expect(Gate::forUser($owner)->allows('manage-payment-accounts'))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('verify-transfers'))->toBeTrue()
        ->and(Gate::forUser($worker)->allows('manage-payment-accounts'))->toBeFalse()
        ->and(Gate::forUser($worker)->allows('verify-transfers'))->toBeFalse();
});

test('a user who is not the owner cannot reach the accounts', function () {
    $worker = User::factory()->create();
    $account = PaymentAccount::factory()->create();

    $this->actingAs($worker)->get('/payment-accounts')->assertForbidden();
    $this->actingAs($worker)->post('/payment-accounts', ['name' => 'x', 'holder_name' => 'y', 'number' => '1'])->assertForbidden();
    $this->actingAs($worker)->patch("/payment-accounts/{$account->id}/toggle")->assertForbidden();
});

test('the owner adds an account, with the number tidied', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->post('/payment-accounts', ['name' => ' جوال باي ', 'holder_name' => 'مصعب', 'number' => '٠٥٩٩  ١٢٣ ٤٥٦'])
        ->assertRedirect('/payment-accounts')
        ->assertSessionHasNoErrors();

    $account = PaymentAccount::sole();

    // Spaces are dropped, so "0599 123 456" and "0599123456" are the same number.
    expect($account->name)->toBe('جوال باي')
        ->and($account->number)->toBe('0599123456')
        ->and($account->is_active)->toBeTrue();

    $this->actingAs($owner)->get('/payment-accounts')->assertOk()->assertSee('0599123456');
});

test('a bank IBAN keeps its letters', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->post('/payment-accounts', ['name' => 'بنك فلسطين', 'holder_name' => 'مصعب', 'number' => 'PS92 PALS 0000 0000 0400 1234 5670'])
        ->assertSessionHasNoErrors();

    expect(PaymentAccount::sole()->number)->toBe('PS92PALS00000000040012345670');
});

test('a duplicate number on the same app is refused with a clear message', function () {
    $owner = User::factory()->owner()->create();
    PaymentAccount::factory()->create(['name' => 'جوال باي', 'number' => '0599123456']);

    $this->actingAs($owner)
        ->post('/payment-accounts', ['name' => 'جوال باي', 'holder_name' => 'مصعب', 'number' => '0599123456'])
        ->assertSessionHasErrors(['number' => 'هذا الرقم مضاف من قبل على جوال باي.']);
});

test('an account in use is hidden instead of deleted', function () {
    $owner = User::factory()->owner()->create();
    $account = PaymentAccount::factory()->create();
    Transaction::factory()->credit('5.00')->create(['method' => Transaction::TRANSFER, 'payment_account_id' => $account->id]);

    $this->actingAs($owner)->delete("/payment-accounts/{$account->id}")->assertSessionHas('error');
    expect($account->fresh())->not->toBeNull();

    $this->actingAs($owner)->patch("/payment-accounts/{$account->id}/toggle");
    expect($account->fresh()->is_active)->toBeFalse();
});

test('an unused account can be deleted', function () {
    $owner = User::factory()->owner()->create();
    $account = PaymentAccount::factory()->create();

    $this->actingAs($owner)->delete("/payment-accounts/{$account->id}")->assertRedirect('/payment-accounts');

    expect(PaymentAccount::count())->toBe(0);
});
