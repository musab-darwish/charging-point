<?php

use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function customerForm(array $overrides = []): array
{
    return [
        'name' => 'أبو محمد الطويل',
        'phone_prefix' => '970',
        'phone_local' => '0599123456',
        'notes' => 'صاحب البقالة',
        ...$overrides,
    ];
}

test('the walk-in customer exists after migrating', function () {
    expect(Customer::walkIn()->is_walk_in)->toBeTrue();
});

test('the list shows customers but not the walk-in', function () {
    Customer::factory()->create(['name' => 'Sami Baker']);

    $this->actingAs($this->user)->get('/customers')
        ->assertOk()
        ->assertSee('Sami Baker')
        ->assertDontSee('زبون عابر');
});

test('search finds by name, local phone and notes', function () {
    Customer::factory()->create(['name' => 'Sami Baker', 'phone' => '970599111222']);
    Customer::factory()->create(['name' => 'Omar Barber', 'phone' => null, 'notes' => 'pays monthly']);

    $this->actingAs($this->user)->get('/customers?q=Sami')->assertSee('Sami Baker')->assertDontSee('Omar Barber');
    $this->actingAs($this->user)->get('/customers?q=0599111')->assertSee('Sami Baker')->assertDontSee('Omar Barber');
    $this->actingAs($this->user)->get('/customers?q=monthly')->assertSee('Omar Barber')->assertDontSee('Sami Baker');
});

test('live search returns only the list', function () {
    Customer::factory()->create(['name' => 'Sami Baker']);

    $this->actingAs($this->user)
        ->get('/customers?q=Sami', ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertSee('Sami Baker')
        ->assertDontSee('<html', false);
});

test('a customer is added with the phone stored in international form', function () {
    $this->actingAs($this->user)
        ->post('/customers', customerForm())
        ->assertRedirect('/customers')
        ->assertSessionHasNoErrors();

    $customer = Customer::where('name', 'أبو محمد الطويل')->first();

    expect($customer->phone)->toBe('970599123456')
        ->and($customer->phone_local)->toBe('0599123456')
        ->and($customer->phone_display)->toBe('0599 123 456')
        ->and($customer->is_walk_in)->toBeFalse();
});

test('the phone is optional and extra spaces in the name are removed', function () {
    $this->actingAs($this->user)
        ->post('/customers', customerForm(['name' => '  سامي   الفران ', 'phone_local' => '']))
        ->assertSessionHasNoErrors();

    expect(Customer::where('name', 'سامي الفران')->first()->phone)->toBeNull();
});

test('the same name cannot be added twice', function () {
    Customer::factory()->create(['name' => 'أبو محمد الطويل']);

    $this->actingAs($this->user)
        ->post('/customers', customerForm(['name' => 'أبو محمد  الطويل']))
        ->assertSessionHasErrors('name');
});

test('a wrong phone number is rejected', function () {
    $this->actingAs($this->user)
        ->post('/customers', customerForm(['phone_local' => '12345']))
        ->assertSessionHasErrors('phone_local');
});

test('a customer can be edited and keep their own name', function () {
    $customer = Customer::factory()->create(['name' => 'أبو محمد الطويل']);

    $this->actingAs($this->user)
        ->put("/customers/{$customer->id}", customerForm(['phone_prefix' => '972', 'phone_local' => '0521234567']))
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->phone)->toBe('972521234567');
});

test('a customer with no records can be deleted', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($this->user)->delete("/customers/{$customer->id}")->assertRedirect('/customers');

    expect(Customer::find($customer->id))->toBeNull();
});

test('the walk-in customer cannot be edited or deleted', function () {
    $walkIn = Customer::walkIn();

    $this->actingAs($this->user)->get("/customers/{$walkIn->id}/edit")->assertNotFound();
    $this->actingAs($this->user)->put("/customers/{$walkIn->id}", customerForm())->assertNotFound();
    $this->actingAs($this->user)->delete("/customers/{$walkIn->id}")->assertNotFound();

    expect(Customer::walkIn()->name)->toBe('زبون عابر');
});

test('guests cannot see customers', function () {
    $this->get('/customers')->assertRedirect('/login');
});
