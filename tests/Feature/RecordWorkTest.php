<?php

use App\Models\Customer;
use App\Models\PriceItem;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DaySheet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->phone = PriceItem::factory()->create(['name' => 'جوال مع شاحن', 'category' => 'charging', 'price' => '1.00']);
    $this->cola = PriceItem::factory()->create(['name' => 'كولا', 'category' => 'drink', 'price' => '3.00']);
});

function workForm(array $overrides = []): array
{
    return [
        'date' => now()->format('Y-m-d'),
        'customer_id' => test()->customer->id,
        'items' => [
            ['price_item_id' => test()->phone->id, 'description' => 'جوال مع شاحن', 'quantity' => 2, 'unit_price' => '1.00'],
            ['price_item_id' => test()->cola->id, 'description' => 'كولا', 'quantity' => 1, 'unit_price' => '3.00'],
        ],
        'form_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('each item becomes a debit with its price stored', function () {
    $this->actingAs($this->user)->post('/work', workForm())->assertRedirect('/')->assertSessionHasNoErrors();

    $debits = $this->customer->transactions()->debits()->orderBy('id')->get();

    expect($debits)->toHaveCount(2)
        ->and($debits[0]->description)->toBe('جوال مع شاحن')
        ->and($debits[0]->quantity)->toBe(2)
        ->and($debits[0]->unit_price)->toBe('1.00')
        ->and($debits[0]->amount)->toBe('2.00')
        ->and($debits[0]->category)->toBe('charging')
        ->and($debits[0]->user_id)->toBe($this->user->id)
        ->and($debits[1]->category)->toBe('drink')
        ->and($this->customer->balance())->toBe('5.00');
});

test('changing a price later does not change what was recorded', function () {
    $this->actingAs($this->user)->post('/work', workForm());

    $this->phone->update(['price' => '1.50']);

    expect($this->customer->balance())->toBe('5.00');
});

test('recording and paying at once settles the day', function () {
    $this->actingAs($this->user)->post('/work', workForm(['pay_now' => '1']));

    $row = (new DaySheet(CarbonImmutable::today()))->rows->first();

    expect($this->customer->balance())->toBe('0.00')
        ->and($row->status->label())->toBe('واصل');
});

test('a customer with an old debt who pays today shows paid for today and keeps the old debt', function () {
    Transaction::factory()->for($this->customer)->debit('20.00')->create(['occurred_at' => now()->subDays(3)]);

    $this->actingAs($this->user)->post('/work', workForm(['pay_now' => '1']));

    $row = (new DaySheet(CarbonImmutable::today()))->rows->first();

    expect($row->status->label())->toBe('واصل')
        ->and($row->work)->toBe('5.00')
        ->and($row->balance)->toBe('20.00');
});

test('a price typed with Arabic digits is understood', function () {
    $form = workForm();
    $form['items'][1]['unit_price'] = '٣٫٥';

    $this->actingAs($this->user)->post('/work', $form)->assertSessionHasNoErrors();

    expect($this->customer->balance())->toBe('5.50');
});

test('a free item is recorded as other', function () {
    $this->actingAs($this->user)->post('/work', workForm([
        'items' => [['price_item_id' => null, 'description' => 'تصليح شاحن', 'quantity' => 1, 'unit_price' => '7']],
    ]))->assertSessionHasNoErrors();

    $debit = $this->customer->transactions()->first();

    expect($debit->category)->toBe('other')
        ->and($debit->description)->toBe('تصليح شاحن')
        ->and($debit->amount)->toBe('7.00');
});

test('a free item needs a description with words, not just a number', function () {
    foreach (['3', '12.5', '-'] as $description) {
        $this->actingAs($this->user)->post('/work', workForm([
            'items' => [['price_item_id' => null, 'description' => $description, 'quantity' => 1, 'unit_price' => '4']],
        ]))->assertSessionHasErrors('items.0.description');
    }

    $this->actingAs($this->user)->post('/work', workForm([
        'items' => [['price_item_id' => null, 'description' => '3 كيلو سكر', 'quantity' => 1, 'unit_price' => '4']],
    ]))->assertSessionHasNoErrors();

    expect(Transaction::count())->toBe(1);
});

test('an item needs a price above zero', function () {
    $this->actingAs($this->user)->post('/work', workForm([
        'items' => [['price_item_id' => $this->phone->id, 'description' => 'جوال', 'quantity' => 1, 'unit_price' => '']],
    ]))->assertSessionHasErrors('items.0.unit_price');

    $this->actingAs($this->user)->post('/work', workForm([
        'items' => [['price_item_id' => $this->phone->id, 'description' => 'جوال', 'quantity' => 1, 'unit_price' => '0']],
    ]))->assertSessionHasErrors('items.0.unit_price');

    expect(Transaction::count())->toBe(0);
});

test('there must be at least one item and a customer', function () {
    $this->actingAs($this->user)->post('/work', workForm(['items' => []]))->assertSessionHasErrors('items');
    $this->actingAs($this->user)->post('/work', workForm(['customer_id' => null]))->assertSessionHasErrors('customer_id');
});

test('a new customer can be added by name while recording', function () {
    $this->actingAs($this->user)->post('/work', workForm(['customer_id' => null, 'new_customer_name' => '  أبو   سامي ']))
        ->assertSessionHasNoErrors();

    $customer = Customer::where('name', 'أبو سامي')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->balance())->toBe('5.00');
});

test('a new name that already exists is rejected', function () {
    $this->actingAs($this->user)
        ->post('/work', workForm(['customer_id' => null, 'new_customer_name' => $this->customer->name]))
        ->assertSessionHasErrors('new_customer_name');
});

test('the walk-in customer must pay on the spot', function () {
    $walkIn = Customer::walkIn();

    $this->actingAs($this->user)->post('/work', workForm(['customer_id' => $walkIn->id]))
        ->assertSessionHasErrors('customer_id');

    $this->actingAs($this->user)->post('/work', workForm(['customer_id' => $walkIn->id, 'pay_now' => '1']))
        ->assertSessionHasNoErrors();

    expect($walkIn->balance())->toBe('0.00')
        ->and($walkIn->transactions()->count())->toBe(3);
});

test('the same form sent twice is recorded once', function () {
    $form = workForm(['pay_now' => '1']);

    $this->actingAs($this->user)->post('/work', $form);
    $this->actingAs($this->user)->post('/work', $form)->assertSessionHas('status', 'هذا الشغل مسجّل من قبل.');

    expect(Transaction::count())->toBe(3);
});

test('the same new-customer form sent twice adds the customer once without an error', function () {
    $form = workForm(['customer_id' => null, 'new_customer_name' => 'أبو سامي']);

    $this->actingAs($this->user)->post('/work', $form);
    $this->actingAs($this->user)->post('/work', $form)->assertSessionHasNoErrors();

    expect(Customer::where('name', 'أبو سامي')->count())->toBe(1);
});

test('work can be recorded on an earlier day', function () {
    $day = now()->subDays(2)->format('Y-m-d');

    $this->actingAs($this->user)->post('/work', workForm(['date' => $day]))->assertRedirect("/day/{$day}");

    expect($this->customer->transactions()->first()->occurred_at->format('Y-m-d'))->toBe($day);
});

test('work cannot be recorded on a day that has not come', function () {
    $this->actingAs($this->user)
        ->post('/work', workForm(['date' => now()->addDay()->format('Y-m-d')]))
        ->assertSessionHasErrors('date');
});
