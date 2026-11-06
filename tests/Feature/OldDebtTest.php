<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DaySheet;
use App\Support\Statement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
    $this->customer = Customer::factory()->create(['name' => 'أبو محمد']);
});

function oldDebtForm(array $overrides = []): array
{
    return [
        'customer_id' => test()->customer->id,
        'amount' => '45',
        'direction' => 'owed',
        'form_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('an old debt becomes one movement owed, dated the day it is moved', function () {
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm())
        ->assertRedirect('/old-debts')
        ->assertSessionHas('status', 'نُقل رصيد أبو محمد: عليه 45.00 ₪.');

    $movement = $this->customer->transactions()->sole();

    expect($movement->type)->toBe(Transaction::DEBIT)
        ->and($movement->category)->toBe(Transaction::OPENING)
        ->and($movement->description)->toBe('رصيد سابق من الدفتر')
        ->and($movement->occurred_at->isToday())->toBeTrue()
        ->and($movement->user_id)->toBe($this->owner->id)
        ->and($this->customer->balance())->toBe('45.00');
});

test('money the customer had in the notebook is moved in their favour', function () {
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm(['amount' => '٧٫٥', 'direction' => 'credit']));

    expect($this->customer->transactions()->sole()->type)->toBe(Transaction::CREDIT)
        ->and($this->customer->balance())->toBe('-7.50');
});

test('a customer is moved over once', function () {
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm());

    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm(['amount' => '10']))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'نُقل رصيد أبو محمد السابق من قبل (45.00 ₪)'));

    expect($this->customer->balance())->toBe('45.00');
});

test('a wrong one can be cancelled and moved again', function () {
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm(['amount' => '54']));
    $wrong = $this->customer->transactions()->sole();

    $this->actingAs($this->owner)->post("/transactions/{$wrong->id}/reverse", ['reason' => 'المبلغ خطأ', 'form_token' => (string) Str::uuid()]);
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm(['amount' => '45']))->assertSessionHas('status');

    expect($this->customer->balance())->toBe('45.00')
        ->and($this->customer->openingBalance()->amount)->toBe('45.00');
});

test('a new customer can be added while moving their debt', function () {
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm(['customer_id' => null, 'new_customer_name' => ' أبو علي ']));

    expect(Customer::where('name', 'أبو علي')->sole()->balance())->toBe('45.00');
});

test('the walk-in customer has no old debt', function () {
    $this->actingAs($this->owner)
        ->post('/old-debts', oldDebtForm(['customer_id' => Customer::walkIn()->id]))
        ->assertSessionHasErrors('customer_id');
});

test('the amount is checked', function () {
    foreach (['', '0', 'كثير', '2.555'] as $amount) {
        $this->actingAs($this->owner)->post('/old-debts', oldDebtForm(['amount' => $amount]))->assertSessionHasErrors('amount');
    }

    expect(Transaction::count())->toBe(0);
});

test('only the owner moves old debts', function () {
    $worker = User::factory()->create();

    $this->actingAs($worker)->get('/old-debts')->assertForbidden();
    $this->actingAs($worker)->post('/old-debts', oldDebtForm())->assertForbidden();

    expect(Transaction::count())->toBe(0);
});

test('an old debt is not that day\'s work, but it is on the statement', function () {
    $this->actingAs($this->owner)->post('/old-debts', oldDebtForm());
    Transaction::factory()->for($this->customer)->debit('2.00')->create(['description' => 'جوال']);

    $sheet = new DaySheet(CarbonImmutable::today());
    $row = $sheet->rows->sole();

    expect($sheet->work)->toBe('2.00')
        ->and($row->items->pluck('description')->all())->toBe(['جوال'])
        ->and($row->balance)->toBe('47.00')
        ->and((new Statement($this->customer))->days->sole()->summary())->toContain('رصيد سابق من الدفتر');
});
