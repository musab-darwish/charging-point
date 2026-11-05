<?php

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DaySheet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
});

function paymentForm(array $overrides = []): array
{
    return [
        'customer_id' => test()->customer->id,
        'amount' => '5',
        'date' => now()->format('Y-m-d'),
        'form_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('a cash payment lowers the balance', function () {
    Transaction::factory()->for($this->customer)->debit('8.00')->create();

    $this->actingAs($this->user)->post('/payments', paymentForm())->assertRedirect('/')->assertSessionHasNoErrors();

    $payment = $this->customer->transactions()->credits()->first();

    expect($payment->method)->toBe(Transaction::CASH)
        ->and($payment->amount)->toBe('5.00')
        ->and($this->customer->balance())->toBe('3.00');
});

test('paying more than today covers part of an old debt', function () {
    Transaction::factory()->for($this->customer)->debit('10.00')->create(['occurred_at' => now()->subWeek()]);
    Transaction::factory()->for($this->customer)->debit('2.00')->create();

    $this->actingAs($this->user)->post('/payments', paymentForm(['amount' => '7']));

    $row = (new DaySheet(CarbonImmutable::today()))->rows->first();

    expect($row->status->label())->toBe('واصل')
        ->and($this->customer->balance())->toBe('5.00');
});

test('a partial payment shows what is left of the day', function () {
    Transaction::factory()->for($this->customer)->debit('5.00')->create();

    $this->actingAs($this->user)->post('/payments', paymentForm(['amount' => '2']));

    $row = (new DaySheet(CarbonImmutable::today()))->rows->first();

    expect($row->status->label())->toBe('متبقي 3.00 ₪');
});

test('the same payment form sent twice is recorded once', function () {
    $form = paymentForm();

    $this->actingAs($this->user)->post('/payments', $form);
    $this->actingAs($this->user)->post('/payments', $form);

    expect($this->customer->transactions()->count())->toBe(1);
});

test('the amount must be above zero', function () {
    $this->actingAs($this->user)->post('/payments', paymentForm(['amount' => '0']))->assertSessionHasErrors('amount');
    $this->actingAs($this->user)->post('/payments', paymentForm(['amount' => '']))->assertSessionHasErrors('amount');
});

test('the walk-in customer has no separate payments', function () {
    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['customer_id' => Customer::walkIn()->id]))
        ->assertSessionHasErrors('customer_id');
});

test('a transfer lowers the balance straight away and waits to be confirmed', function () {
    Transaction::factory()->for($this->customer)->debit('8.00')->create();
    $account = PaymentAccount::factory()->create(['name' => 'جوال باي']);

    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['method' => 'transfer', 'payment_account_id' => $account->id, 'reference' => '٤٥٦٧٨']))
        ->assertRedirect('/')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', fn ($status) => str_contains($status, 'جوال باي'));

    $payment = $this->customer->transactions()->credits()->sole();

    expect($payment->method)->toBe(Transaction::TRANSFER)
        ->and($payment->payment_account_id)->toBe($account->id)
        ->and($payment->reference)->toBe('45678')
        ->and($payment->verified_at)->toBeNull()
        ->and($payment->user_id)->toBe($this->user->id)
        ->and($this->customer->balance())->toBe('3.00');
});

test('the transfer number is optional', function () {
    $account = PaymentAccount::factory()->create();

    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['method' => 'transfer', 'payment_account_id' => $account->id]))
        ->assertSessionHasNoErrors();

    expect($this->customer->transactions()->sole()->reference)->toBeNull();
});

test('a transfer needs the account it arrived on', function () {
    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['method' => 'transfer']))
        ->assertSessionHasErrors(['payment_account_id' => 'اختر الحساب الذي وصلت عليه الحوالة.']);

    expect($this->customer->transactions()->count())->toBe(0);
});

test('a hidden account takes no new transfers', function () {
    $hidden = PaymentAccount::factory()->inactive()->create();

    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['method' => 'transfer', 'payment_account_id' => $hidden->id]))
        ->assertSessionHasErrors(['payment_account_id' => 'هذا الحساب غير متاح للحوالات.']);
});

test('cash never keeps an account or a transfer number', function () {
    $account = PaymentAccount::factory()->create();

    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['method' => 'cash', 'payment_account_id' => $account->id, 'reference' => '123']))
        ->assertSessionHasNoErrors();

    $payment = $this->customer->transactions()->sole();

    expect($payment->method)->toBe(Transaction::CASH)
        ->and($payment->payment_account_id)->toBeNull()
        ->and($payment->reference)->toBeNull();
});

test('an unknown payment method is refused', function () {
    $this->actingAs($this->user)
        ->post('/payments', paymentForm(['method' => 'cheque']))
        ->assertSessionHasErrors('method');
});
