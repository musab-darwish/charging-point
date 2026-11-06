<?php

use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DaySheet;
use App\Support\Statement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
});

function reverseForm(array $overrides = []): array
{
    return ['reason' => 'سُجّلت بالخطأ', 'form_token' => (string) Str::uuid(), ...$overrides];
}

test('cancelling an item adds an opposite movement and clears it from the balance', function () {
    $item = Transaction::factory()->for($this->customer)->debit('3.00')->create(['occurred_at' => now()->subDays(2)]);
    Transaction::factory()->for($this->customer)->debit('1.00')->create();

    $this->actingAs($this->user)->from('/')
        ->post("/transactions/{$item->id}/reverse", reverseForm())
        ->assertRedirect('/')
        ->assertSessionHas('status');

    $reversal = $item->fresh()->reversal;

    expect($reversal->type)->toBe(Transaction::CREDIT)
        ->and($reversal->amount)->toBe('3.00')
        ->and($reversal->description)->toBe('سُجّلت بالخطأ')
        ->and($reversal->user_id)->toBe($this->user->id)
        ->and($reversal->occurred_at->equalTo($item->occurred_at))->toBeTrue()
        ->and($item->fresh()->amount)->toBe('3.00')
        ->and($this->customer->balance())->toBe('1.00');
});

test('cancelling a payment puts it back on the balance', function () {
    Transaction::factory()->for($this->customer)->debit('10.00')->create();
    $payment = Transaction::factory()->for($this->customer)->credit('4.00')->create();

    $this->actingAs($this->user)->post("/transactions/{$payment->id}/reverse", reverseForm());

    expect($payment->fresh()->reversal->type)->toBe(Transaction::DEBIT)
        ->and($this->customer->balance())->toBe('10.00');
});

test('a movement is cancelled only once', function () {
    $item = Transaction::factory()->for($this->customer)->debit('3.00')->create();

    $this->actingAs($this->user)->post("/transactions/{$item->id}/reverse", reverseForm());
    $this->actingAs($this->user)->post("/transactions/{$item->id}/reverse", reverseForm())->assertSessionHas('error');

    expect(Transaction::where('reverses_id', $item->id)->count())->toBe(1)
        ->and($this->customer->balance())->toBe('0.00');
});

test('a cancellation itself cannot be cancelled', function () {
    $item = Transaction::factory()->for($this->customer)->debit('3.00')->create();
    $this->actingAs($this->user)->post("/transactions/{$item->id}/reverse", reverseForm());
    $reversal = $item->fresh()->reversal;

    $this->actingAs($this->user)->post("/transactions/{$reversal->id}/reverse", reverseForm())->assertSessionHas('error');

    expect($reversal->fresh()->reversal)->toBeNull()
        ->and($this->customer->balance())->toBe('0.00');
});

test('the same cancel form sent twice cancels once', function () {
    $item = Transaction::factory()->for($this->customer)->debit('3.00')->create();
    $other = Transaction::factory()->for($this->customer)->debit('2.00')->create();
    $form = reverseForm();

    $this->actingAs($this->user)->post("/transactions/{$item->id}/reverse", $form);
    $this->actingAs($this->user)->post("/transactions/{$other->id}/reverse", $form);

    expect($other->fresh()->reversal)->toBeNull()
        ->and($this->customer->balance())->toBe('2.00');
});

test('a cancellation must say why', function () {
    $item = Transaction::factory()->for($this->customer)->debit('3.00')->create();

    $this->actingAs($this->user)->post("/transactions/{$item->id}/reverse", reverseForm(['reason' => '  ']))
        ->assertSessionHasErrors(['reason' => 'اكتب سبب الإلغاء.']);
    $this->actingAs($this->user)->post("/transactions/{$item->id}/reverse", reverseForm(['reason' => '33']))
        ->assertSessionHasErrors('reason');

    expect($item->fresh()->reversal)->toBeNull();
});

test('cancelling a freezer pickup charge puts the item back in the freezer', function () {
    $freezeItem = FreezeItem::factory()->for($this->customer)->pickedUp()->create(['picked_up_by' => $this->user->id, 'stored_at' => now()->subDays(3)]);
    $charge = Transaction::factory()->for($this->customer)->debit('5.00')->create(['category' => 'freezing', 'freeze_item_id' => $freezeItem->id]);

    $this->actingAs($this->user)->post("/transactions/{$charge->id}/reverse", reverseForm(['reason' => 'السعر خطأ']));

    $freezeItem->refresh();

    // Back in the freezer from the day it first arrived, not from today.
    expect($freezeItem->isInFreezer())->toBeTrue()
        ->and($freezeItem->daysStored())->toBe(3)
        ->and($freezeItem->picked_up_by)->toBeNull()
        ->and($freezeItem->transaction)->toBeNull()
        ->and($this->customer->balance())->toBe('0.00');
});

test('a cancelled transfer cannot be confirmed', function () {
    $owner = User::factory()->owner()->create();
    $transfer = Transaction::factory()->for($this->customer)->credit('6.00')->create([
        'method' => Transaction::TRANSFER,
        'payment_account_id' => PaymentAccount::factory()->create()->id,
    ]);

    $this->actingAs($owner)->post("/transactions/{$transfer->id}/reverse", reverseForm(['reason' => 'لم تصل الحوالة']));
    $this->actingAs($owner)->patch(route('transfers.verify', $transfer))->assertSessionHas('error');

    expect($transfer->fresh()->isVerified())->toBeFalse();
});

test('a cancelled item drops out of the day without counting as a payment', function () {
    $wrong = Transaction::factory()->for($this->customer)->debit('3.00')->create(['description' => '3']);
    Transaction::factory()->for($this->customer)->debit('2.00')->create(['description' => 'جوال']);

    $this->actingAs($this->user)->post("/transactions/{$wrong->id}/reverse", reverseForm());

    $row = (new DaySheet(CarbonImmutable::today()))->rows->sole();

    expect($row->work)->toBe('2.00')
        ->and($row->paid)->toBe('0.00')
        ->and($row->status->label())->toBe('لم يُدفع بعد')
        ->and($row->items->pluck('description')->all())->toBe(['جوال'])
        ->and($row->entries)->toHaveCount(2)
        ->and($row->entries->firstWhere('id', $wrong->id)->reversal->description)->toBe('سُجّلت بالخطأ');
});

test('the statement leaves cancelled movements out of the day but lists them', function () {
    $wrong = Transaction::factory()->for($this->customer)->debit('3.00')->create(['description' => '3', 'occurred_at' => now()->subDay()]);
    Transaction::factory()->for($this->customer)->debit('2.00')->create(['description' => 'جوال', 'occurred_at' => now()->subDay()]);

    $this->actingAs($this->user)->post("/transactions/{$wrong->id}/reverse", reverseForm());

    $day = (new Statement($this->customer))->days->sole();

    expect($day->summary())->toBe('جوال')
        ->and($day->work)->toBe('2.00')
        ->and($day->paid)->toBe('0.00')
        ->and($day->balanceAfter)->toBe('2.00')
        ->and($day->cancelled->pluck('id')->all())->toBe([$wrong->id])
        ->and($day->entries())->toHaveCount(2);
});

test('the day page strikes a cancelled movement through and says why', function () {
    $wrong = Transaction::factory()->for($this->customer)->debit('3.00')->create(['description' => 'باور']);
    Transaction::factory()->for($this->customer)->debit('2.00')->create(['description' => 'جوال']);

    $this->actingAs($this->user)->post("/transactions/{$wrong->id}/reverse", reverseForm(['reason' => 'سُجّلت لزبون آخر']));

    $this->actingAs($this->user)->get('/')
        ->assertOk()
        ->assertSeeInOrder(['باور', 'أُلغيت', 'سُجّلت لزبون آخر', $this->user->name])
        // The address sits in a JSON data attribute, where slashes are escaped.
        ->assertSee(trim(json_encode(route('transactions.reverse', Transaction::where('description', 'جوال')->first())), '"'), false)
        ->assertDontSee(trim(json_encode(route('transactions.reverse', $wrong)), '"'), false);
});

test('a refused reason reopens the cancel dialog on the same movement', function () {
    $item = Transaction::factory()->for($this->customer)->debit('3.00')->create();

    $this->actingAs($this->user)->from('/')
        ->post("/transactions/{$item->id}/reverse", reverseForm(['reason' => '', 'reverse_for' => $item->id]));

    $this->actingAs($this->user)->get('/')
        ->assertSee('data-reopen="'.$item->id.'"', false)
        ->assertSee('اكتب سبب الإلغاء.');
});
