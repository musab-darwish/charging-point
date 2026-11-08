<?php

use App\Models\User;

function manifest(): array
{
    return json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
}

test('the login page links the manifest and the home screen icon', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">', false);
});

test('pages behind the login link the manifest too', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('day.today'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
});

test('the manifest opens the app full screen on the day page, in Arabic', function () {
    $manifest = manifest();

    expect($manifest)
        ->name->toBe(config('app.name'))
        ->short_name->toBe(config('app.name'))
        ->display->toBe('standalone')
        ->start_url->toBe('/')
        ->lang->toBe('ar')
        ->dir->toBe('rtl');
});

test('every icon the manifest names exists at the size it claims', function () {
    $icons = collect(manifest()['icons']);

    expect($icons->pluck('purpose'))->toContain('any', 'maskable');

    $icons->each(function (array $icon) {
        $file = public_path(ltrim($icon['src'], '/'));
        expect($file)->toBeFile();

        [$width, $height] = getimagesize($file);
        expect("{$width}x{$height}")->toBe($icon['sizes']);
    });

    [$width] = getimagesize(public_path('icons/apple-touch-icon.png'));
    expect($width)->toBe(180);
});
