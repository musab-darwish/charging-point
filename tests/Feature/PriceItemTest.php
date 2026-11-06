<?php

use App\Models\PriceItem;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
});

test('the owner sees the price list grouped by kind', function () {
    PriceItem::factory()->create(['name' => 'Phone with charger', 'category' => 'charging', 'price' => '1.00']);
    PriceItem::factory()->create(['name' => 'Cola', 'category' => 'drink', 'price' => '3.00']);

    $this->actingAs($this->owner)->get('/prices')
        ->assertOk()
        ->assertSeeInOrder(['الشحن', 'Phone with charger', 'المشروبات', 'Cola']);
});

test('a user who is not the owner cannot manage prices', function () {
    $worker = User::factory()->create();
    $item = PriceItem::factory()->create();

    $this->actingAs($worker)->get('/prices')->assertForbidden();
    $this->actingAs($worker)->post('/prices', ['name' => 'x', 'category' => 'charging'])->assertForbidden();
    $this->actingAs($worker)->patch("/prices/{$item->id}/toggle")->assertForbidden();
});

test('an item is added with its price', function () {
    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Phone with charger', 'category' => 'charging', 'price' => '1.5'])
        ->assertRedirect('/prices')
        ->assertSessionHasNoErrors();

    $item = PriceItem::where('name', 'Phone with charger')->first();

    expect($item->price)->toBe('1.50')
        ->and($item->is_active)->toBeTrue();
});

test('a price typed with Arabic digits is understood', function () {
    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Power bank', 'category' => 'charging', 'price' => '٢٫٥'])
        ->assertSessionHasNoErrors();

    expect(PriceItem::where('name', 'Power bank')->first()->price)->toBe('2.50');
});

test('an item can have no fixed price', function () {
    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Battery', 'category' => 'charging', 'price' => ''])
        ->assertSessionHasNoErrors();

    expect(PriceItem::where('name', 'Battery')->first()->hasFixedPrice())->toBeFalse();
});

test('the name must be unique and the price valid', function () {
    PriceItem::factory()->create(['name' => 'Cola']);

    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Cola', 'category' => 'drink', 'price' => '3'])
        ->assertSessionHasErrors('name');

    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Juice', 'category' => 'drink', 'price' => '-1'])
        ->assertSessionHasErrors('price');

    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Juice', 'category' => 'drink', 'price' => '1.555'])
        ->assertSessionHasErrors('price');

    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Juice', 'category' => 'snacks', 'price' => '1'])
        ->assertSessionHasErrors('category');
});

test('an item can be edited and keep its own name', function () {
    $item = PriceItem::factory()->create(['name' => 'Cola', 'price' => '3.00']);

    $this->actingAs($this->owner)
        ->put("/prices/{$item->id}", ['name' => 'Cola', 'category' => 'drink', 'price' => '3.50'])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->price)->toBe('3.50')
        ->and($item->fresh()->category)->toBe('drink');
});

test('items can be moved within their kind', function () {
    $first = PriceItem::factory()->create(['name' => 'First']);
    $second = PriceItem::factory()->create(['name' => 'Second']);

    $this->actingAs($this->owner)->patch("/prices/{$second->id}/move/up");

    expect(PriceItem::ordered()->pluck('name')->all())->toBe(['Second', 'First']);

    $this->actingAs($this->owner)->patch("/prices/{$second->id}/move/down");

    expect(PriceItem::ordered()->pluck('name')->all())->toBe(['First', 'Second']);
});

test('an item can be hidden and shown again', function () {
    $item = PriceItem::factory()->create();

    $this->actingAs($this->owner)->patch("/prices/{$item->id}/toggle");
    expect($item->fresh()->is_active)->toBeFalse()
        ->and(PriceItem::active()->count())->toBe(0);

    $this->actingAs($this->owner)->patch("/prices/{$item->id}/toggle");
    expect($item->fresh()->is_active)->toBeTrue();
});

test('an unused item can be deleted', function () {
    $item = PriceItem::factory()->create();

    $this->actingAs($this->owner)->delete("/prices/{$item->id}")->assertRedirect('/prices');

    expect(PriceItem::find($item->id))->toBeNull();
});

test('cooling is a kind of item on the price list, apart from the freezer', function () {
    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Cake', 'category' => 'cooling', 'price' => '1.5'])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->owner)->get('/prices')->assertSeeInOrder(['التبريد', 'Cake']);

    $this->actingAs($this->owner)
        ->post('/prices', ['name' => 'Chicken', 'category' => 'freezing', 'price' => '3'])
        ->assertSessionHasErrors('category');
});
