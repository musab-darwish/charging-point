@php
    // Sent back after a refused form: reopen with what was typed.
    $reopen = old('back_to') === 'transfers' && $errors->any();
    $oldCustomer = $reopen && old('customer_id') ? \App\Models\Customer::find(old('customer_id')) : null;
    $oldState = $oldCustomer ? ['id' => $oldCustomer->id, 'name' => $oldCustomer->name, 'balance' => $oldCustomer->balance(), 'oneTime' => $oldCustomer->is_one_time] : null;
    $error = fn (string $key) => $reopen ? $errors->first($key) : null;
    $errorIcon = '<svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg>';
@endphp

{{-- «تسجيل حوالة»: a transfer from any existing customer, recorded right from the transfers page
     (resources/js/behaviors/record-transfer.js). --}}
<dialog id="record-transfer" aria-labelledby="record-transfer-title" data-record-transfer
    data-open-on-load="{{ $reopen ? 'true' : 'false' }}" data-old-customer='{{ json_encode($oldState) }}'
    class="m-auto w-[min(100%-2rem,30rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <form method="POST" action="{{ route('payments.store') }}" data-submit-lock data-stay data-transfer-form
        class="max-h-[calc(100dvh-2rem)] animate-drop overflow-y-auto rounded-3xl border border-rule bg-white p-6 shadow-2xl shadow-ink/25 sm:p-7">
        @csrf
        <input type="hidden" name="method" value="transfer">
        <input type="hidden" name="back_to" value="transfers">
        <input type="hidden" name="form_token" value="{{ $transferToken }}">
        <input type="hidden" name="customer_id" data-customer-id>
        <input type="hidden" data-new-customer>

        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-credit-soft text-credit">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3l4 4-4 4" /><path d="M3 7h18" /><path d="M7 21l-4-4 4-4" /><path d="M21 17H3" /></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="record-transfer-title" class="text-xl font-extrabold">تسجيل حوالة</h2>
                <p class="text-sm text-ink-muted">حوالة وصلت من زبون على أحد حسابات التحويل.</p>
            </div>
            <button type="button" data-transfer-cancel aria-label="إغلاق" class="flex size-11 shrink-0 items-center justify-center rounded-lg text-ink-muted transition hover:bg-paper">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>

        {{-- Who sent it: existing customers only --}}
        <div class="mt-6">
            <p class="mb-1.5 text-sm font-bold">من الزبون</p>
            @include('partials.customer-picker', ['pickerId' => 'transfer', 'next' => '#transfer-amount', 'existingOnly' => true])
            <p data-error-for="transfer-customer-search" @unless ($error('customer_id')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $errorIcon !!}<span data-error-text>{{ $error('customer_id') }}</span></p>
        </div>

        <label for="transfer-amount" class="mb-1.5 mt-5 block text-sm font-bold">المبلغ</label>
        <div class="relative" dir="ltr">
            <input id="transfer-amount" name="amount" type="text" inputmode="decimal" autocomplete="off" value="{{ $reopen ? old('amount') : '' }}"
                @if ($error('amount')) aria-invalid="true" @endif
                class="block h-16 w-full rounded-xl border border-rule-strong bg-white ps-4 pe-12 text-end text-3xl font-extrabold tabular-nums transition focus:border-credit focus:outline-none focus:ring-4 focus:ring-credit/15 aria-invalid:border-debit aria-invalid:focus:border-debit aria-invalid:focus:ring-debit/15">
            <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xl font-bold text-ink-muted">₪</span>
        </div>
        <p data-error-for="transfer-amount" @unless ($error('amount')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $errorIcon !!}<span data-error-text>{{ $error('amount') }}</span></p>

        {{-- Which account it arrived on --}}
        <div class="mt-5">
            @if ($paymentAccounts->isEmpty())
                <p id="transfer-account" tabindex="-1" class="rounded-xl border border-dashed border-rule-strong bg-sheet px-4 py-3 text-sm leading-relaxed">
                    لا توجد حسابات تحويل بعد.
                    @can('manage-payment-accounts')
                        أضفها من <a href="{{ route('payment-accounts.index') }}" class="font-bold text-brand underline underline-offset-4">حسابات التحويل</a>.
                    @else
                        يضيفها المالك من صفحة حسابات التحويل.
                    @endcan
                </p>
            @else
                <fieldset id="transfer-account" data-error-group="transfer-account" tabindex="-1" class="outline-none">
                    <legend class="mb-1.5 block text-sm font-bold">وصلت على حساب</legend>
                    <div class="space-y-2">
                        @foreach ($paymentAccounts as $account)
                            <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border-2 border-rule px-3 py-2 transition hover:border-credit/50 has-checked:border-credit has-checked:bg-credit-soft has-focus-visible:ring-4 has-focus-visible:ring-credit/15">
                                <input type="radio" name="payment_account_id" value="{{ $account->id }}" data-transfer-account
                                    @checked($reopen ? (string) old('payment_account_id') === (string) $account->id : $paymentAccounts->count() === 1)
                                    class="size-4.5 shrink-0 accent-credit">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-bold">{{ $account->name }}</span>
                                    <span class="block truncate text-xs text-ink-muted">باسم {{ $account->holder_name }}</span>
                                </span>
                                <span dir="ltr" class="shrink-0 text-sm font-bold tabular-nums text-ink-muted">{{ $account->number }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
            <p data-error-for="transfer-account" @unless ($error('payment_account_id')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $errorIcon !!}<span data-error-text>{{ $error('payment_account_id') }}</span></p>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="transfer-date" class="mb-1.5 block text-sm font-bold">تاريخ الحوالة</label>
                <input id="transfer-date" name="date" type="date" max="{{ today()->format('Y-m-d') }}" value="{{ $reopen ? old('date') : today()->format('Y-m-d') }}"
                    @if ($error('date')) aria-invalid="true" @endif
                    class="block h-12 w-full rounded-xl border border-rule-strong bg-white px-3 font-bold tabular-nums transition focus:border-credit focus:outline-none focus:ring-4 focus:ring-credit/15 aria-invalid:border-debit">
                <p data-error-for="transfer-date" @unless ($error('date')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $errorIcon !!}<span data-error-text>{{ $error('date') }}</span></p>
            </div>
            <div>
                <label for="transfer-reference" class="mb-1.5 block text-sm font-bold">رقم الحوالة <span class="font-medium text-ink-muted">(اختياري)</span></label>
                <input id="transfer-reference" name="reference" type="text" inputmode="numeric" autocomplete="off" dir="ltr" maxlength="50" value="{{ $reopen ? old('reference') : '' }}"
                    @if ($error('reference')) aria-invalid="true" @endif
                    class="block h-12 w-full rounded-xl border border-rule-strong bg-white px-4 text-end font-bold tabular-nums transition focus:border-credit focus:outline-none focus:ring-4 focus:ring-credit/15 aria-invalid:border-debit">
                <p data-error-for="transfer-reference" @unless ($error('reference')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit">{!! $errorIcon !!}<span data-error-text>{{ $error('reference') }}</span></p>
            </div>
        </div>

        @can('verify-transfers')
            {{-- The owner already saw it in the app: record it confirmed --}}
            <label class="mt-5 flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border border-rule bg-sheet p-3.5 has-checked:border-credit/50 has-checked:bg-credit-soft">
                <input type="checkbox" name="confirmed" value="1" @checked($reopen && old('confirmed')) class="mt-1 size-5 shrink-0 rounded accent-credit">
                <span>
                    <span class="block font-bold">وصلت وتأكدت منها</span>
                    <span class="block text-sm text-ink-muted">رأيتها في التطبيق، فتُسجَّل مؤكدة ولا تظهر في «لم تتأكد».</span>
                </span>
            </label>
        @else
            <p class="mt-4 text-xs leading-relaxed text-ink-muted">تُخصم الحوالة من رصيد الزبون فوراً، وتبقى «لم تتأكد» حتى يتحقق المالك من وصولها.</p>
        @endcan

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" data-transfer-cancel class="inline-flex h-12 items-center justify-center rounded-xl border-2 border-rule-strong font-bold transition hover:bg-paper active:scale-[.97]">إلغاء</button>
            <button type="submit" class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-credit font-bold text-white transition hover:bg-credit/90 active:scale-[.97] aria-busy:opacity-80">
                <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                تسجيل الحوالة
            </button>
        </div>
    </form>
</dialog>
