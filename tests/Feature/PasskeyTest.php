<?php

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

beforeEach(function () {
    $this->user = User::factory()->create(['username' => 'musab', 'password' => 'secret-pass-123']);
});

function passkeyFor(User $user, string $name = 'Android · 6-11-2026'): Passkey
{
    return Passkey::forceCreate([
        'user_id' => $user->id,
        'name' => $name,
        'credential_id' => bin2hex(random_bytes(16)),
        'credential' => ['fake' => true],
    ]);
}

test('a user can hold passkeys, saved under their username', function () {
    expect($this->user)->toBeInstanceOf(PasskeyUser::class)
        ->and($this->user->getPasskeyUsername())->toBe('musab')
        ->and($this->user->hasPasskeysEnabled())->toBeFalse();

    passkeyFor($this->user);

    expect($this->user->hasPasskeysEnabled())->toBeTrue();
});

test('the fingerprint page asks for the password first', function () {
    $this->actingAs($this->user)->get('/fingerprint')->assertRedirect('/confirm-password');

    $this->actingAs($this->user)->post('/confirm-password', ['password' => 'wrong'])
        ->assertSessionHasErrors(['password' => 'كلمة المرور غير صحيحة.']);

    $this->actingAs($this->user)->post('/confirm-password', ['password' => 'secret-pass-123'])
        ->assertRedirect('/fingerprint');

    $this->actingAs($this->user)->get('/fingerprint')->assertOk()->assertSee('الدخول بالبصمة');
});

test('adding a passkey also needs a recent password confirmation', function () {
    $this->actingAs($this->user)->getJson(route('passkey.registration-options'))->assertStatus(423);

    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('passkey.registration-options'))
        ->assertOk()
        ->assertJsonPath('options.user.name', 'musab');
});

test('the page lists this user\'s devices and removes one', function () {
    $mine = passkeyFor($this->user, 'Android · 6-11-2026');
    $theirs = passkeyFor(User::factory()->create(), 'iPhone · 1-11-2026');

    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()])
        ->get('/fingerprint')
        ->assertSee('Android · 6-11-2026')
        ->assertDontSee('iPhone · 1-11-2026');

    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('passkey.destroy', $theirs))
        ->assertForbidden();

    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()])
        ->from('/fingerprint')
        ->delete(route('passkey.destroy', $mine))
        ->assertRedirect('/fingerprint')
        ->assertSessionHas('status', 'أُزيل الدخول بالبصمة من هذا الجهاز.');

    expect(Passkey::count())->toBe(1);
});

test('a deactivated user cannot sign in with a fingerprint', function () {
    $passkey = passkeyFor($this->user);

    expect(Passkeys::allowsLogin(Request::create('/passkeys/login', 'POST'), $passkey))->toBeTrue();

    $this->user->deactivate();

    expect(Passkeys::allowsLogin(Request::create('/passkeys/login', 'POST'), $passkey->fresh()))->toBeFalse();
});

test('a guest gets sign-in options, and the login page offers the fingerprint', function () {
    $this->getJson(route('passkey.login-options'))->assertOk()->assertJsonStructure(['options' => ['challenge']]);

    $this->get('/login')->assertSee('الدخول بالبصمة')->assertSee('data-passkey-verify', false);
});

test('the day page offers the fingerprint until it is set up', function () {
    $this->actingAs($this->user)->get('/')->assertSee('data-passkey-offer', false);

    passkeyFor($this->user);

    $this->actingAs($this->user)->get('/')->assertDontSee('data-passkey-offer', false);
});
