<?php

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->customer = Customer::factory()->create(['name' => 'أبو محمد']);
    $this->jawwal = PaymentAccount::factory()->create(['name' => 'جوال باي']);
    $this->bank = PaymentAccount::factory()->create(['name' => 'بنك فلسطين']);
});

function transferOf(string $amount, PaymentAccount $account, array $attributes = []): Transaction
{
    return Transaction::factory()->for(test()->customer)->credit($amount)->create([
        'method' => Transaction::TRANSFER,
        'payment_account_id' => $account->id,
        ...$attributes,
    ]);
}

test('only transfers still waiting are listed', function () {
    $waiting = transferOf('5.00', $this->jawwal);
    transferOf('6.00', $this->jawwal, ['verified_at' => now()]);
    $cancelled = transferOf('7.00', $this->bank);
    Transaction::factory()->for($this->customer)->debit('7.00')->create(['reverses_id' => $cancelled->id, 'occurred_at' => $cancelled->occurred_at]);
    Transaction::factory()->for($this->customer)->credit('8.00')->create();

    expect(Transaction::awaitingConfirmation()->pluck('id')->all())->toBe([$waiting->id]);
});

test('the page groups them by account, with each account\'s total', function () {
    transferOf('5.00', $this->jawwal, ['occurred_at' => now()->subDays(2), 'reference' => '7781']);
    transferOf('3.00', $this->jawwal);
    transferOf('20.00', $this->bank);

    $this->actingAs(User::factory()->owner()->create())->get('/transfers')
        ->assertOk()
        ->assertSeeInOrder(['لم تتأكد', '3', 'مجموعها', '28.00'])
        ->assertSeeInOrder(['بنك فلسطين', '20.00', 'جوال باي', '8.00', 'أبو محمد', '7781', '5.00', 'أبو محمد', '3.00']);
});

test('everyone sees the list, only the owner confirms from it', function () {
    $transfer = transferOf('5.00', $this->jawwal);

    $this->actingAs(User::factory()->create())->get('/transfers')
        ->assertOk()
        ->assertSee('أبو محمد')
        ->assertDontSee(route('transfers.verify', $transfer));

    $this->actingAs(User::factory()->owner()->create())->get('/transfers')
        ->assertSee(route('transfers.verify', $transfer));
});

test('a confirmed transfer leaves the list', function () {
    $transfer = transferOf('5.00', $this->jawwal);
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->from('/transfers')->patch(route('transfers.verify', $transfer))->assertRedirect('/transfers');

    $this->actingAs($owner)->get('/transfers')->assertSee('كل الحوالات مؤكدة');
});
