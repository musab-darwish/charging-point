<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function validUser(array $overrides = []): array
{
    return [
        'name' => 'سامي',
        'username' => 'sami',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
});

test('the owner can add a user', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser())
        ->assertRedirect('/users')
        ->assertSessionHasNoErrors();

    $user = User::where('username', 'sami')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('سامي')
        ->and($user->is_owner)->toBeFalse()
        ->and(Hash::check('secret-pass', $user->password))->toBeTrue();
});

test('the form cannot make someone an owner', function () {
    $this->actingAs($this->owner)->post('/users', validUser(['is_owner' => '1']));

    expect(User::where('username', 'sami')->first()->is_owner)->toBeFalse();
});

test('the name is required', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('the username is required', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser(['username' => '']))
        ->assertSessionHasErrors('username');
});

test('the username must be unique', function () {
    User::factory()->create(['username' => 'sami']);

    $this->actingAs($this->owner)
        ->post('/users', validUser())
        ->assertSessionHasErrors('username');
});

test('the username cannot contain spaces', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser(['username' => 'sami ali']))
        ->assertSessionHasErrors('username');
});

test('the username must be lowercase', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser(['username' => 'Sami']))
        ->assertSessionHasErrors('username');
});

test('the password needs at least 8 characters', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser(['password' => 'short', 'password_confirmation' => 'short']))
        ->assertSessionHasErrors('password');
});

test('the password must be confirmed', function () {
    $this->actingAs($this->owner)
        ->post('/users', validUser(['password_confirmation' => 'something-else']))
        ->assertSessionHasErrors('password');
});

test('a user who is not the owner cannot add users', function () {
    $worker = User::factory()->create();

    $this->actingAs($worker)->post('/users', validUser())->assertForbidden();

    expect(User::where('username', 'sami')->exists())->toBeFalse();
});
