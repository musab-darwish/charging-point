<?php

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountMessage;

beforeEach(function () {
    $this->travelTo('2026-11-06 12:00');
    $this->customer = Customer::factory()->create(['name' => 'أبو محمد', 'phone' => '970599123456']);
});

function entry(string $type, string $amount, string $at, array $attributes = []): Transaction
{
    $factory = Transaction::factory()->for(test()->customer);

    return ($type === 'debit' ? $factory->debit($amount) : $factory->credit($amount))->create(['occurred_at' => $at, ...$attributes]);
}

test('it starts from the last payment, with the balance left after it', function () {
    entry('debit', '15.00', '2026-11-01 10:00');
    entry('credit', '5.00', '2026-11-02 10:00');
    entry('debit', '10.00', '2026-11-02 18:00');
    entry('credit', '20.00', '2026-11-03 09:00');
    entry('debit', '4.00', '2026-11-04 10:00', ['description' => 'باور']);
    entry('debit', '1.00', '2026-11-04 11:00', ['description' => 'جوال']);
    entry('debit', '1.00', '2026-11-05 11:00', ['description' => 'جوال']);

    $message = new AccountMessage($this->customer);

    expect($message->lastPayment->amount)->toBe('20.00')
        ->and($message->balanceAfterPayment)->toBe('0.00')
        ->and($message->sinceTotal)->toBe('6.00')
        ->and($message->balance)->toBe('6.00')
        ->and($message->balance)->toBe($this->customer->balance())
        ->and($message->short())->toContain('آخر دفعة منك: 20.00 ₪ يوم الثلاثاء 3-11-2026، وكان حسابك بعدها مسكّراً.')
        ->and($message->short())->toContain('سُجّل عليك بعدها: 6.00 ₪.')
        ->and($message->short())->toContain('المتبقي عليك الآن: 6.00 ₪');
});

test('the full statement lists each day since the last payment', function () {
    entry('debit', '30.00', '2026-11-01 10:00');
    entry('credit', '25.00', '2026-11-03 09:00');
    entry('debit', '4.00', '2026-11-04 10:00', ['description' => 'باور']);
    entry('debit', '2.00', '2026-11-04 11:00', ['description' => 'جوال', 'quantity' => 2]);
    entry('debit', '1.00', '2026-11-05 11:00', ['description' => 'جوال']);

    $message = new AccountMessage($this->customer);

    expect($message->full())->toContain('وبقي عليك بعدها 5.00 ₪.')
        ->and($message->full())->toContain('• الأربعاء 4-11-2026: باور + جوال ×2 = 6.00 ₪')
        ->and($message->full())->toContain('• الخميس 5-11-2026: جوال = 1.00 ₪')
        ->and($message->full())->toContain('المتبقي عليك الآن: 12.00 ₪')
        ->and($message->short())->not->toContain('باور');
});

test('work on the day of the last payment, after it, comes after it', function () {
    entry('debit', '3.00', '2026-11-04 09:00');
    entry('credit', '3.00', '2026-11-04 10:00');
    entry('debit', '2.00', '2026-11-04 17:00', ['description' => 'لابتوب']);

    $message = new AccountMessage($this->customer);

    expect($message->sinceTotal)->toBe('2.00')
        ->and($message->full())->toContain('• الأربعاء 4-11-2026: لابتوب = 2.00 ₪');
});

test('cancelled movements and their cancellations are left out', function () {
    entry('debit', '10.00', '2026-11-01 10:00');
    entry('credit', '10.00', '2026-11-02 10:00');
    $wrongPayment = entry('credit', '50.00', '2026-11-04 10:00');
    entry('debit', '50.00', '2026-11-04 10:00', ['reverses_id' => $wrongPayment->id, 'description' => 'المبلغ خطأ']);
    $wrongItem = entry('debit', '9.00', '2026-11-05 10:00', ['description' => 'سُجّل خطأ']);
    entry('credit', '9.00', '2026-11-05 10:00', ['reverses_id' => $wrongItem->id, 'description' => 'لزبون آخر']);
    entry('debit', '2.00', '2026-11-05 12:00', ['description' => 'جوال']);

    $message = new AccountMessage($this->customer);

    expect($message->lastPayment->amount)->toBe('10.00')
        ->and($message->full())->not->toContain('50.00')
        ->and($message->full())->not->toContain('9.00')
        ->and($message->full())->not->toContain('سُجّل خطأ')
        ->and($message->full())->toContain('• الخميس 5-11-2026: جوال = 2.00 ₪')
        ->and($message->balance)->toBe('2.00');
});

test('with no payment it starts from the first movement, notebook balance included', function () {
    entry('debit', '40.00', '2026-11-01 10:00', ['category' => Transaction::OPENING, 'description' => 'رصيد سابق من الدفتر']);
    entry('debit', '2.00', '2026-11-03 10:00', ['description' => 'جوال']);

    $message = new AccountMessage($this->customer);

    expect($message->lastPayment)->toBeNull()
        ->and($message->short())->not->toContain('آخر دفعة')
        ->and($message->short())->toContain('سُجّل عليك: 42.00 ₪.')
        ->and($message->full())->toContain('• الأحد 1-11-2026: رصيد سابق من الدفتر = 40.00 ₪');
});

test('the active transfer accounts are offered only when something is owed', function () {
    PaymentAccount::factory()->create(['name' => 'جوال باي', 'number' => '0599000111', 'holder_name' => 'مصعب']);
    PaymentAccount::factory()->inactive()->create(['name' => 'بنك قديم']);
    entry('debit', '5.00', '2026-11-05 10:00');

    expect((new AccountMessage($this->customer))->short())->toContain('• جوال باي: 0599000111 (مصعب)')
        ->not->toContain('بنك قديم');

    entry('credit', '5.00', '2026-11-06 10:00');

    expect((new AccountMessage($this->customer->fresh()))->short())->toContain('حسابك مسكّر')
        ->not->toContain('للتحويل');
});

test('the WhatsApp link opens the customer\'s number with the text', function () {
    entry('debit', '5.00', '2026-11-05 10:00');
    $message = new AccountMessage($this->customer);

    expect($message->whatsappUrl('مرحبا 5 ₪'))->toBe('https://wa.me/970599123456?text='.rawurlencode('مرحبا 5 ₪'))
        ->and((new AccountMessage(Customer::factory()->create(['phone' => null])))->whatsappUrl('x'))->toBeNull();
});

test('the dialog gets both messages and their WhatsApp links', function () {
    entry('debit', '5.00', '2026-11-05 10:00', ['description' => 'باور']);

    $this->actingAs(User::factory()->create())
        ->getJson(route('customers.message', $this->customer))
        ->assertOk()
        ->assertJsonPath('name', 'أبو محمد')
        ->assertJsonPath('short.text', fn ($text) => str_contains($text, 'المتبقي عليك الآن: 5.00 ₪'))
        ->assertJsonPath('full.text', fn ($text) => str_contains($text, 'باور = 5.00 ₪'))
        ->assertJsonPath('short.whatsapp', fn ($url) => str_starts_with($url, 'https://wa.me/970599123456?text='));
});

test('the walk-in customer has no account message', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('customers.message', Customer::walkIn()))
        ->assertNotFound();
});

test('the customer page and the debtors list open the message', function () {
    entry('debit', '5.00', '2026-11-05 10:00');
    $url = route('customers.message', $this->customer);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('customers.show', $this->customer))->assertSee($url);
    $this->actingAs($user)->get('/debtors')->assertSee($url);
});
