<?php

use App\Models\User;

test('the owner sees the users list', function () {
    $owner = User::factory()->owner()->create();
    $worker = User::factory()->create(['name' => 'Sami Worker']);

    $this->actingAs($owner)
        ->get('/users')
        ->assertOk()
        ->assertSee($owner->name)
        ->assertSee($worker->name);
});

test('a user who is not the owner gets 403', function () {
    $worker = User::factory()->create();

    $this->actingAs($worker)->get('/users')->assertForbidden();
});

test('guests are sent to login from the users page', function () {
    $this->get('/users')->assertRedirect('/login');
});

test('only the owner sees the users link', function () {
    $owner = User::factory()->owner()->create();
    $worker = User::factory()->create();

    $this->actingAs($owner)->get('/')->assertSee(route('users.index'));
    $this->actingAs($worker)->get('/')->assertDontSee(route('users.index'));
});
