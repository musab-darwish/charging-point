<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('every page carries the browser safety headers', function () {
    foreach (['/login', '/no-such-page'] as $page) {
        $this->get($page)
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin');
    }

    $this->actingAs(User::factory()->create())->get('/')->assertHeader('X-Frame-Options', 'DENY');
});

test('no files are served from storage', function () {
    expect(collect(Route::getRoutes()->getRoutes())->map->uri()->filter(fn ($uri) => str_starts_with($uri, 'storage/')))->toBeEmpty();
});

test('every page needs sign-in, except the sign-in itself', function () {
    $open = collect(Route::getRoutes()->getRoutes())
        ->reject(fn ($route) => collect($route->gatherMiddleware())->contains(fn ($middleware) => str_starts_with($middleware, 'auth')))
        ->map->uri()
        ->values()
        ->all();

    expect($open)->toEqualCanonicalizing(['login', 'login', 'passkeys/login/options', 'passkeys/login', 'up']);
});
