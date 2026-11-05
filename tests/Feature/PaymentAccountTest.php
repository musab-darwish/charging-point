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
