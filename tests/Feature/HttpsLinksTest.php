<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

afterEach(fn () => URL::forceScheme(null));

test('with an https address, links are built as https behind the proxy', function () {
    config(['app.url' => 'https://shop-laptop.example.ts.net']);
    (new AppServiceProvider(app()))->boot();

    expect(route('login'))->toStartWith('https://');
});

test('with a plain http address, links stay http', function () {
    config(['app.url' => 'http://localhost:8000']);
    URL::forceScheme(null);
    (new AppServiceProvider(app()))->boot();

    expect(route('login'))->toStartWith('http://');
});
