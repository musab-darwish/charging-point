<?php

use App\Models\Customer;
use App\Models\PriceItem;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->item = PriceItem::factory()->create(['name' => 'جوال', 'category' => 'charging', 'price' => '1.00']);
});

/**
 * The work form as the day page sends it.
 */
function stayWorkForm(array $overrides = []): array
{
    return [
        'date' => now()->format('Y-m-d'),
        'customer_id' => test()->customer->id,
        'items' => [['price_item_id' => test()->item->id, 'description' => 'جوال', 'quantity' => 1, 'unit_price' => '1.00']],
        'form_token' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('a form sent in the background gets the address to open instead of a redirect', function () {
    $this->actingAs($this->user)
        ->post('/work', stayWorkForm(), ['X-Stay' => '1'])
        ->assertOk()
        ->assertExactJson(['location' => route('day.today')])
        ->assertSessionHas('status');

    expect($this->customer->balance())->toBe('1.00');
});

test('it names the customer page when the form came from there', function () {
    $this->actingAs($this->user)
        ->post('/payments', [
            'customer_id' => $this->customer->id,
            'amount' => '2.00',
            'date' => now()->format('Y-m-d'),
            'form_token' => (string) Str::uuid(),
            'back_to_customer' => $this->customer->id,
        ], ['X-Stay' => '1'])
        ->assertOk()
        ->assertExactJson(['location' => route('customers.show', $this->customer)]);
});

test('it names an earlier day page for work recorded on that day', function () {
    $day = now()->subDays(2)->format('Y-m-d');

    $this->actingAs($this->user)
        ->post('/work', stayWorkForm(['date' => $day]), ['X-Stay' => '1'])
        ->assertExactJson(['location' => route('day.show', $day)]);
});

test('a refused form still comes back with its errors kept for the page', function () {
    $this->actingAs($this->user)
        ->from(route('day.today'))
        ->post('/work', stayWorkForm(['items' => []]), ['X-Stay' => '1'])
        ->assertOk()
        ->assertExactJson(['location' => route('day.today')])
        ->assertSessionHasErrors('items');
});

test('without the header every form redirects exactly as before', function () {
    $this->actingAs($this->user)
        ->post('/work', stayWorkForm())
        ->assertRedirect(route('day.today'));

    $this->actingAs($this->user)
        ->from(route('day.today'))
        ->post('/work', stayWorkForm(['items' => []]))
        ->assertRedirect(route('day.today'))
        ->assertSessionHasErrors('items');
});

test('a page that is not a redirect is left alone', function () {
    $this->actingAs($this->user)
        ->get(route('day.today'), ['X-Stay' => '1'])
        ->assertOk()
        ->assertSee('شغل اليوم');
});
