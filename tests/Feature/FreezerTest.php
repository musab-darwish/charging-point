<?php

use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create(['name' => 'أبو محمد']);
});

function storeForm(array $overrides = []): array
{
    return [
        'customer_id' => test()->customer->id,
        'description' => 'كيكة وسط',
        'quantity' => '2',
        'form_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

function pickUpForm(array $overrides = []): array
{
    return ['price' => '3', 'form_token' => (string) Str::uuid(), ...$overrides];
}

test('putting something in the freezer charges nothing', function () {
    $this->actingAs($this->user)->post('/freezer', storeForm())->assertRedirect('/freezer')->assertSessionHasNoErrors();

    $item = FreezeItem::sole();

    expect($item->description)->toBe('كيكة وسط')
        ->and($item->quantity)->toBe(2)
        ->and($item->isInFreezer())->toBeTrue()
        ->and($item->stored_at->isToday())->toBeTrue()
        ->and($item->user_id)->toBe($this->user->id)
        ->and(Transaction::count())->toBe(0)
        ->and($this->customer->balance())->toBe('0.00');
});

test('an item already in the freezer can be entered with the day it arrived', function () {
    $this->actingAs($this->user)->post('/freezer', storeForm(['stored_on' => now()->subDays(3)->format('Y-m-d')]));

    expect(FreezeItem::sole()->daysStored())->toBe(3);
});

test('the arrival day cannot be in the future', function () {
    $this->actingAs($this->user)
        ->post('/freezer', storeForm(['stored_on' => now()->addDay()->format('Y-m-d')]))
        ->assertSessionHasErrors('stored_on');
});

test('a new customer can be added while putting the item in', function () {
    $this->actingAs($this->user)->post('/freezer', storeForm(['customer_id' => null, 'new_customer_name' => '  أبو علي   الطويل ']));

    expect(FreezeItem::sole()->customer->name)->toBe('أبو علي الطويل');
});

test('the walk-in customer cannot leave something in the freezer', function () {
    $this->actingAs($this->user)
        ->post('/freezer', storeForm(['customer_id' => Customer::walkIn()->id]))
        ->assertSessionHasErrors(['customer_id' => 'اختر زبوناً باسمه، فالغرض يُستلم لاحقاً.']);
});

test('the same freezer form sent twice is recorded once', function () {
    $form = storeForm();

    $this->actingAs($this->user)->post('/freezer', $form);
    $this->actingAs($this->user)->post('/freezer', $form);

    expect(FreezeItem::count())->toBe(1);
});

test('the charge is made only at pickup, at the price written then', function () {
    $item = FreezeItem::factory()->for($this->customer)->create(['description' => 'كيكة وسط', 'quantity' => 2]);
    $picker = User::factory()->create();

    expect(Transaction::count())->toBe(0);

    $this->actingAs($picker)
        ->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '٣٫٥']))
        ->assertRedirect('/freezer')
        ->assertSessionHasNoErrors();

    $charge = Transaction::sole();
    $item->refresh();

    expect($charge->type)->toBe(Transaction::DEBIT)
        ->and($charge->category)->toBe('freezing')
        ->and($charge->amount)->toBe('3.50')
        ->and($charge->description)->toBe('تجميد: كيكة وسط (2)')
        ->and($charge->freeze_item_id)->toBe($item->id)
        ->and($item->isInFreezer())->toBeFalse()
        ->and($item->picked_up_by)->toBe($picker->id)
        ->and($this->customer->balance())->toBe('3.50');
});

test('a free pickup makes no charge', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '0']));

    expect($item->fresh()->isInFreezer())->toBeFalse()
        ->and(Transaction::count())->toBe(0);
});

test('picking up and paying on the spot leaves nothing owed', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '4', 'pay_now' => '1']));

    expect($this->customer->transactions()->count())->toBe(2)
        ->and($this->customer->transactions()->credits()->sole()->method)->toBe(Transaction::CASH)
        ->and($this->customer->balance())->toBe('0.00');
});

test('an item is charged once even if picked up twice', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)->post("/freezer/{$item->id}/pickup", pickUpForm());
    $this->actingAs($this->user)->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '9']))
        ->assertSessionHas('status', 'هذا الغرض مُسلَّم من قبل.');

    expect(Transaction::sole()->amount)->toBe('3.00');
});

test('the price at pickup is required and cannot be negative', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '']))->assertSessionHasErrors('price');
    $this->actingAs($this->user)->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '-1']))->assertSessionHasErrors('price');

    expect($item->fresh()->isInFreezer())->toBeTrue();
});

test('an item entered by mistake can be deleted while still in the freezer', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)->delete("/freezer/{$item->id}")->assertRedirect('/freezer');

    expect(FreezeItem::count())->toBe(0);
});

test('a picked-up item cannot be deleted', function () {
    $item = FreezeItem::factory()->for($this->customer)->pickedUp()->create();

    $this->actingAs($this->user)->delete("/freezer/{$item->id}")->assertSessionHas('error');

    expect($item->fresh())->not->toBeNull();
});

test('the freezer page lists what is waiting and what was picked up', function () {
    FreezeItem::factory()->for($this->customer)->create(['description' => 'كيكة وسط', 'stored_at' => now()->subDays(3)]);
    $done = FreezeItem::factory()->for($this->customer)->pickedUp()->create(['description' => 'قنينة مي']);
    Transaction::factory()->for($this->customer)->debit('2.00')->create(['freeze_item_id' => $done->id, 'category' => 'freezing']);

    $this->actingAs($this->user)->get('/freezer')
        ->assertOk()
        ->assertSeeInOrder(['في الفريزر الآن', 'كيكة وسط', 'أبو محمد', '3 أيام', 'استُلمت مؤخراً', 'قنينة مي', '2.00 ₪']);
});

test('a price that is not a number is refused in plain words', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)
        ->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => 'ثلاثة', 'pickup_for' => $item->id]))
        ->assertSessionHasErrors(['price' => 'اكتب السعر بالأرقام، مثل 3 أو 2.50.']);

    $this->actingAs($this->user)
        ->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '2.505']))
        ->assertSessionHasErrors(['price' => 'يقبل السعر منزلتين عشريتين على الأكثر.']);

    $this->actingAs($this->user)
        ->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '10000']))
        ->assertSessionHasErrors(['price' => 'السعر أكبر من المسموح (9999.99 ₪).']);

    expect($item->fresh()->isInFreezer())->toBeTrue();
});

test('a refused price reopens the pickup on the same item', function () {
    $item = FreezeItem::factory()->for($this->customer)->create();

    $this->actingAs($this->user)
        ->from('/freezer')
        ->post("/freezer/{$item->id}/pickup", pickUpForm(['price' => '', 'pickup_for' => $item->id]))
        ->assertRedirect('/freezer');

    $this->actingAs($this->user)->get('/freezer')
        ->assertSee('data-reopen="'.$item->id.'"', false)
        ->assertSee('اكتب سعر التجميد، أو 0 إن كان بدون مقابل.');
});

test('a quantity that is not a whole number from 1 to 99 is refused', function () {
    foreach (['0', '100', '1.5', 'كثير'] as $quantity) {
        $this->actingAs($this->user)
            ->post('/freezer', storeForm(['quantity' => $quantity]))
            ->assertSessionHasErrors(['quantity' => 'الكمية عدد صحيح من 1 إلى 99.']);
    }

    expect(FreezeItem::count())->toBe(0);
});
