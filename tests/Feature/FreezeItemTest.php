<?php

use App\Models\Customer;
use App\Models\FreezeItem;
use App\Models\Transaction;
use Illuminate\Database\QueryException;

test('days in the freezer are counted by date', function () {
    $this->travelTo('2026-11-06 09:00');

    expect(FreezeItem::factory()->create(['stored_at' => '2026-11-06 08:00'])->daysStored())->toBe(0)
        ->and(FreezeItem::factory()->create(['stored_at' => '2026-11-05 23:50'])->daysStored())->toBe(1)
        ->and(FreezeItem::factory()->create(['stored_at' => '2026-11-01 12:00'])->daysStored())->toBe(5);
});

test('the count stops on the day it is picked up', function () {
    $this->travelTo('2026-11-10 09:00');

    $item = FreezeItem::factory()->create(['stored_at' => '2026-11-01 12:00', 'picked_up_at' => '2026-11-04 18:00']);

    expect($item->daysStored())->toBe(3);
});

test('the freezer list holds what is still there, longest-waiting first', function () {
    $newer = FreezeItem::factory()->create(['stored_at' => now()->subDay()]);
    $older = FreezeItem::factory()->create(['stored_at' => now()->subDays(4)]);
    FreezeItem::factory()->pickedUp()->create();

    expect(FreezeItem::inFreezer()->pluck('id')->all())->toBe([$older->id, $newer->id]);
});

test('recently picked up covers the last seven days', function () {
    $recent = FreezeItem::factory()->create(['picked_up_at' => now()->subDays(2)]);
    FreezeItem::factory()->create(['picked_up_at' => now()->subDays(9)]);
    FreezeItem::factory()->create();

    expect(FreezeItem::recentlyPickedUp()->pluck('id')->all())->toBe([$recent->id]);
});

test('the label shows a quantity above one', function () {
    expect(FreezeItem::factory()->make(['description' => 'كيكة وسط', 'quantity' => 2])->label())->toBe('كيكة وسط (2)')
        ->and(FreezeItem::factory()->make(['description' => 'كريمة', 'quantity' => 1])->label())->toBe('كريمة')
        ->and(FreezeItem::factory()->make(['description' => 'كريمة'])->label())->toBe('كريمة');
});

test('only an item still in the freezer can be deleted', function () {
    expect(FreezeItem::factory()->create()->canBeDeleted())->toBeTrue()
        ->and(FreezeItem::factory()->pickedUp()->create()->canBeDeleted())->toBeFalse();
});

test('a customer who left something in the freezer can no longer be deleted', function () {
    $customer = Customer::factory()->create();

    expect($customer->canBeDeleted())->toBeTrue();

    FreezeItem::factory()->for($customer)->create();

    expect($customer->canBeDeleted())->toBeFalse()
        ->and(fn () => $customer->delete())->toThrow(QueryException::class);
});

test('an item charged at pickup cannot be deleted from under its charge', function () {
    $item = FreezeItem::factory()->pickedUp()->create();
    Transaction::factory()->for($item->customer)->debit('3.00')->create(['freeze_item_id' => $item->id, 'category' => 'freezing']);

    expect($item->transaction->amount)->toBe('3.00')
        ->and(fn () => $item->delete())->toThrow(QueryException::class);
});
