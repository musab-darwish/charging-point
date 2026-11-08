<?php

use App\Models\Customer;
use App\Models\PriceItem;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DaySheet;
use App\Support\DayStatus;
use App\Support\Debtors;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function () {
    Carbon::setTestNow('2026-11-08 10:00:00');
    $this->user = User::factory()->create();
    $this->charge = PriceItem::factory()->create(['name' => 'جوال مع شاحن', 'category' => 'charging', 'price' => '2.00']);
});

afterEach(fn () => Carbon::setTestNow());

/**
 * The work form for the walk-in customer, named for one visit.
 */
function oneTimeForm(array $overrides = []): array
{
    return [
        'date' => now()->format('Y-m-d'),
        'customer_id' => Customer::walkIn()->id,
        'one_time' => '1',
        'one_time_name' => 'أبو سامي',
        'items' => [
            ['price_item_id' => test()->charge->id, 'description' => 'جوال مع شاحن', 'quantity' => 1, 'unit_price' => '2.00'],
        ],
        'form_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('the walk-in named for one visit becomes a one-time customer who pays on collection', function () {
    $this->actingAs($this->user)->post('/work', oneTimeForm(['one_time_phone' => '0599 123 456']))
        ->assertRedirect('/')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'تم تسجيل شغل أبو سامي على حسابه.');

    $customer = Customer::where('name', 'أبو سامي')->sole();

    expect($customer->is_one_time)->toBeTrue()
        ->and($customer->phone)->toBe('970599123456')
        ->and($customer->balance())->toBe('2.00')
        ->and(Customer::walkIn()->transactions()->count())->toBe(0);

    $row = (new DaySheet(CarbonImmutable::today()))->rows->sole();
    expect($row->customer->is($customer))->toBeTrue()
        ->and($row->status->state)->toBe(DayStatus::UNPAID);
});

test('a phone typed with Arabic digits and the 972 prefix is stored the usual way', function () {
    $this->actingAs($this->user)->post('/work', oneTimeForm(['one_time_phone' => '٠٥٩٩١٢٣٤٥٦', 'one_time_phone_prefix' => '972']))
        ->assertSessionHasNoErrors();

    expect(Customer::where('name', 'أبو سامي')->sole()->phone)->toBe('972599123456');
});

test('paying on collection settles the one-time customer like anyone else', function () {
    $this->actingAs($this->user)->post('/work', oneTimeForm());
    $customer = Customer::where('name', 'أبو سامي')->sole();

    $this->actingAs($this->user)->post('/payments', [
        'customer_id' => $customer->id,
        'amount' => '2.00',
        'date' => now()->format('Y-m-d'),
        'form_token' => (string) Str::uuid(),
    ])->assertSessionHasNoErrors();

    expect($customer->balance())->toBe('0.00')
        ->and((new DaySheet(CarbonImmutable::today()))->rows->sole()->status->state)->toBe(DayStatus::PAID);
});

test('one who leaves without paying shows on the debtors list by name', function () {
    $this->actingAs($this->user)->post('/work', oneTimeForm());

    $debtors = new Debtors;

    expect($debtors->owing->map(fn ($row) => [$row->customer->name, $row->balance])->all())->toBe([['أبو سامي', '2.00']]);
});

test('without the one-visit tick the walk-in still pays on the spot, as before', function () {
    $walkIn = Customer::walkIn();
    $form = oneTimeForm(['one_time' => '0']);

    $this->actingAs($this->user)->post('/work', $form)->assertSessionHasErrors('customer_id');

    $this->actingAs($this->user)->post('/work', [...$form, 'pay_now' => '1'])->assertSessionHasNoErrors();

    expect($walkIn->balance())->toBe('0.00')
        ->and(Customer::where('is_one_time', true)->count())->toBe(0)
        ->and(Customer::where('name', 'أبو سامي')->exists())->toBeFalse();
});

test('the one-visit customer needs a name in letters', function (?string $name, string $message) {
    $this->actingAs($this->user)->post('/work', oneTimeForm(['one_time_name' => $name]))
        ->assertSessionHasErrors(['one_time_name' => $message]);

    expect(Transaction::count())->toBe(0)
        ->and(Customer::where('is_one_time', true)->count())->toBe(0);
})->with([
    'empty' => [null, 'اكتب اسم الزبون ليُعرف عند استلام غرضه.'],
    'digits only' => ['123', 'اكتب اسم الزبون بالحروف.'],
]);

test('a wrong phone is refused with the usual message', function () {
    $this->actingAs($this->user)->post('/work', oneTimeForm(['one_time_phone' => '12345']))
        ->assertSessionHasErrors(['one_time_phone' => 'رقم الجوال غير صحيح. اكتبه هكذا: 0599123456']);
});

test('only the walk-in customer can be named for one visit', function () {
    $regular = Customer::factory()->create();

    $this->actingAs($this->user)->post('/work', oneTimeForm(['customer_id' => $regular->id]))
        ->assertSessionHasErrors('customer_id');

    expect(Transaction::count())->toBe(0);
});

test('a name already taken gets the day added, then a number', function () {
    Customer::factory()->create(['name' => 'أحمد']);

    $this->actingAs($this->user)->post('/work', oneTimeForm(['one_time_name' => 'أحمد']))->assertSessionHasNoErrors();
    $this->actingAs($this->user)->post('/work', oneTimeForm(['one_time_name' => 'أحمد']))->assertSessionHasNoErrors();

    expect(Customer::oneTime()->orderBy('id')->pluck('name')->all())->toBe(['أحمد (8-11)', 'أحمد (8-11) 2']);
});

test('the same one-visit form sent twice adds one customer and records once', function () {
    $form = oneTimeForm();

    $this->actingAs($this->user)->post('/work', $form);
    $this->actingAs($this->user)->post('/work', $form)->assertSessionHas('status', 'هذا الشغل مسجّل من قبل.');

    expect(Customer::oneTime()->count())->toBe(1)
        ->and(Transaction::count())->toBe(1);
});

test('one-time customers stay off the customer list, and have their own filter', function () {
    Customer::factory()->create(['name' => 'دائم']);
    Customer::factory()->oneTime()->create(['name' => 'عابر بالاسم']);

    $this->actingAs($this->user)->get(route('customers.index'))
        ->assertSee('دائم')
        ->assertDontSee('عابر بالاسم');

    $this->actingAs($this->user)->get(route('customers.index', ['kind' => 'one-time']))
        ->assertSee('عابر بالاسم')
        ->assertDontSee('>دائم<', false);
});

test('the picker offers a one-time customer for a week after they were last seen', function () {
    $recent = Customer::factory()->oneTime()->create(['name' => 'زار أمس']);
    $old = Customer::factory()->oneTime()->create(['name' => 'زار قديماً']);
    $regularOld = Customer::factory()->create(['name' => 'دائم قديم']);

    Transaction::factory()->for($recent)->debit('2.00')->create(['occurred_at' => now()->subDay()]);
    Transaction::factory()->for($old)->debit('2.00')->create(['occurred_at' => now()->subDays(8)]);
    Transaction::factory()->for($regularOld)->debit('2.00')->create(['occurred_at' => now()->subDays(60)]);

    $names = collect($this->actingAs($this->user)->getJson(route('customers.search', ['q' => 'زار']))->json())->pluck('name');
    expect($names->all())->toBe(['زار أمس']);

    $found = $this->actingAs($this->user)->getJson(route('customers.search', ['q' => 'دائم قديم']))->json();
    expect($found)->toHaveCount(1)
        ->and($found[0]['oneTime'])->toBeFalse();
});

test('a one-time customer can be made a regular one', function () {
    $customer = Customer::factory()->oneTime()->create(['name' => 'رجع']);

    $this->actingAs($this->user)->patch(route('customers.make-permanent', $customer))
        ->assertRedirect(route('customers.show', $customer));

    expect($customer->fresh()->is_one_time)->toBeFalse();
    $this->actingAs($this->user)->get(route('customers.index'))->assertSee('رجع');
});

test('existing customers stay regular after the change', function () {
    $customer = Customer::factory()->create();

    expect($customer->fresh()->is_one_time)->toBeFalse()
        ->and(Customer::walkIn()->is_one_time)->toBeFalse();
});
