<?php

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create();
    $this->worker = User::factory()->create();
    $this->customer = Customer::factory()->create(['name' => 'أبو سامي']);
    $this->account = PaymentAccount::factory()->create(['name' => 'جوال باي']);
});

/**
 * The «تسجيل حوالة» window on the transfers page.
 */
function transferForm(array $overrides = []): array
{
    return [
        'customer_id' => test()->customer->id,
        'amount' => '20',
        'method' => 'transfer',
        'payment_account_id' => test()->account->id,
        'reference' => '48213',
        'date' => now()->format('Y-m-d'),
        'form_token' => (string) Str::uuid(),
        'back_to' => 'transfers',
        ...$overrides,
    ];
}

test('a transfer recorded from the transfers page goes back there and waits to be confirmed', function () {
    $this->actingAs($this->worker)->post('/payments', transferForm())
        ->assertRedirect(route('transfers.index'))
        ->assertSessionHas('status', 'تم تسجيل حوالة 20.00 ₪ من أبو سامي على جوال باي، بانتظار التأكد من وصولها.');

    $transfer = Transaction::sole();

    expect($transfer->method)->toBe(Transaction::TRANSFER)
        ->and($transfer->verified_at)->toBeNull()
        ->and($transfer->user_id)->toBe($this->worker->id)
        ->and($this->customer->balance())->toBe('-20.00');

    $this->actingAs($this->worker)->get(route('transfers.index'))->assertSee('أبو سامي');
});

test('the owner can record a transfer already confirmed', function () {
    $this->actingAs($this->owner)->post('/payments', transferForm(['confirmed' => '1']))
        ->assertRedirect(route('transfers.index'))
        ->assertSessionHas('status', 'تم تسجيل حوالة 20.00 ₪ من أبو سامي على جوال باي، وتأكد وصولها.');

    expect(Transaction::sole()->verified_at)->not->toBeNull()
        ->and(Transaction::awaitingConfirmation()->count())->toBe(0);
});

test('anyone else asking to confirm is ignored: the transfer still waits', function () {
    $this->actingAs($this->worker)->post('/payments', transferForm(['confirmed' => '1']))
        ->assertSessionHas('status', 'تم تسجيل حوالة 20.00 ₪ من أبو سامي على جوال باي، بانتظار التأكد من وصولها.');

    expect(Transaction::sole()->verified_at)->toBeNull();
});

test('cash is never marked confirmed', function () {
    $this->actingAs($this->owner)->post('/payments', transferForm(['method' => 'cash', 'confirmed' => '1']))
        ->assertSessionHasNoErrors();

    expect(Transaction::sole()->verified_at)->toBeNull();
});

test('the same window sent twice records one transfer', function () {
    $form = transferForm(['confirmed' => '1']);

    $this->actingAs($this->owner)->post('/payments', $form);
    $this->actingAs($this->owner)->post('/payments', $form)
        ->assertRedirect(route('transfers.index'))
        ->assertSessionHas('status', 'هذه الدفعة مسجّلة من قبل.');

    expect(Transaction::count())->toBe(1);
});

test('a transfer can be recorded on an earlier day', function () {
    $this->actingAs($this->worker)->post('/payments', transferForm(['date' => now()->subDays(3)->format('Y-m-d')]))
        ->assertSessionHasNoErrors();

    expect(Transaction::sole()->occurred_at->isSameDay(now()->subDays(3)))->toBeTrue();
});

test('a transfer cannot be dated in the future, with a message in Arabic', function () {
    $this->actingAs($this->worker)->post('/payments', transferForm(['date' => now()->addDay()->format('Y-m-d')]))
        ->assertSessionHasErrors(['date' => 'لا يمكن تسجيل دفعة بتاريخ لم يأتِ بعد.']);
});

test('it only goes back to pages it knows', function () {
    $this->actingAs($this->worker)->post('/payments', transferForm(['back_to' => 'https://example.com']))
        ->assertSessionHasErrors('back_to');

    expect(Transaction::count())->toBe(0);
});

test('the walk-in customer cannot send a transfer', function () {
    $this->actingAs($this->worker)->post('/payments', transferForm(['customer_id' => Customer::walkIn()->id]))
        ->assertSessionHasErrors('customer_id');
});

test('everyone sees «تسجيل حوالة», and only the owner can tick it confirmed', function () {
    $this->actingAs($this->worker)->get(route('transfers.index'))
        ->assertOk()
        ->assertSee('تسجيل حوالة')
        ->assertSee('data-no-new', false)
        ->assertDontSee('وصلت وتأكدت منها');

    $this->actingAs($this->owner)->get(route('transfers.index'))
        ->assertOk()
        ->assertSee('وصلت وتأكدت منها');
});
