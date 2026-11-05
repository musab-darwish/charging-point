<?php

use App\Models\Customer;
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

    $this->actingAs($this->user)->post('/payments/cash', paymentForm())->assertRedirect('/')->assertSessionHasNoErrors();

    $payment = $this->customer->transactions()->credits()->first();

    expect($payment->method)->toBe(Transaction::CASH)
        ->and($payment->amount)->toBe('5.00')
        ->and($this->customer->balance())->toBe('3.00');
});

test('paying more than today covers part of an old debt', function () {
    Transaction::factory()->for($this->customer)->debit('10.00')->create(['occurred_at' => now()->subWeek()]);
    Transaction::factory()->for($this->customer)->debit('2.00')->create();

    $this->actingAs($this->user)->post('/payments/cash', paymentForm(['amount' => '7']));

    $row = (new DaySheet(CarbonImmutable::today()))->rows->first();

    expect($row->status->label())->toBe('واصل')
        ->and($this->customer->balance())->toBe('5.00');
});

test('a partial payment shows what is left of the day', function () {
    Transaction::factory()->for($this->customer)->debit('5.00')->create();

    $this->actingAs($this->user)->post('/payments/cash', paymentForm(['amount' => '2']));

    $row = (new DaySheet(CarbonImmutable::today()))->rows->first();

    expect($row->status->label())->toBe('متبقي 3.00 ₪');
});

test('the same payment form sent twice is recorded once', function () {
    $form = paymentForm();

    $this->actingAs($this->user)->post('/payments/cash', $form);
    $this->actingAs($this->user)->post('/payments/cash', $form);

    expect($this->customer->transactions()->count())->toBe(1);
});

test('the amount must be above zero', function () {
    $this->actingAs($this->user)->post('/payments/cash', paymentForm(['amount' => '0']))->assertSessionHasErrors('amount');
    $this->actingAs($this->user)->post('/payments/cash', paymentForm(['amount' => '']))->assertSessionHasErrors('amount');
});

test('the walk-in customer has no separate payments', function () {
    $this->actingAs($this->user)
        ->post('/payments/cash', paymentForm(['customer_id' => Customer::walkIn()->id]))
        ->assertSessionHasErrors('customer_id');
});
