<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
    $this->worker = User::factory()->create(['password' => 'old-password']);
});

test('the owner can change a user password', function () {
    $this->actingAs($this->owner)
        ->put("/users/{$this->worker->id}/password", [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/users')
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-password', $this->worker->fresh()->password))->toBeTrue();
});

test('the new password needs 8 characters and a matching confirmation', function () {
    $this->actingAs($this->owner)
        ->put("/users/{$this->worker->id}/password", ['password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors('password');

    $this->actingAs($this->owner)
        ->put("/users/{$this->worker->id}/password", ['password' => 'new-password', 'password_confirmation' => 'different'])
        ->assertSessionHasErrors('password');

    expect(Hash::check('old-password', $this->worker->fresh()->password))->toBeTrue();
});

test('the owner can deactivate and reactivate a user', function () {
    $this->actingAs($this->owner)->patch("/users/{$this->worker->id}/deactivate");
    expect($this->worker->fresh()->isDeactivated())->toBeTrue();

    $this->actingAs($this->owner)->patch("/users/{$this->worker->id}/activate");
    expect($this->worker->fresh()->isDeactivated())->toBeFalse();
});

test('the owner cannot be deactivated', function () {
    $this->actingAs($this->owner)
        ->patch("/users/{$this->owner->id}/deactivate")
        ->assertSessionHas('error');

    expect($this->owner->fresh()->isDeactivated())->toBeFalse();
});

test('a user who is not the owner cannot change passwords or deactivate', function () {
    $other = User::factory()->create();

    $this->actingAs($this->worker)
        ->put("/users/{$other->id}/password", ['password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertForbidden();

    $this->actingAs($this->worker)->patch("/users/{$other->id}/deactivate")->assertForbidden();

    expect($other->fresh()->isDeactivated())->toBeFalse();
});
