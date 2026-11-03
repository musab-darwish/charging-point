<?php

use App\Models\User;

test('a user can log in with the right password', function () {
    $user = User::factory()->create(['password' => 'secret-pass']);

    $this->post('/login', ['username' => $user->username, 'password' => 'secret-pass'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('a wrong password is rejected', function () {
    $user = User::factory()->create(['password' => 'secret-pass']);

    $this->post('/login', ['username' => $user->username, 'password' => 'wrong-pass'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('a deactivated user cannot log in', function () {
    $user = User::factory()->deactivated()->create(['password' => 'secret-pass']);

    $this->post('/login', ['username' => $user->username, 'password' => 'secret-pass'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('a user deactivated while logged in is logged out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertOk();

    $user->deactivate();

    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});
