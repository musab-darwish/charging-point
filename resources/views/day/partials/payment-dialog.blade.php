{{-- "واصل": a payment for one customer, cash or transfer, opened from their card or their page
     (see resources/js/behaviors/payment-dialog.js). --}}
<dialog id="payment-dialog" aria-labelledby="payment-dialog-title"
    class="m-auto w-[min(100%-2rem,28rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <form method="POST" action="{{ route('payments.store') }}" data-submit-lock data-payment-form
        class="max-h-[calc(100dvh-2rem)] animate-drop overflow-y-auto rounded-3xl border border-rule bg-white p-6 shadow-2xl shadow-ink/25 sm:p-7">
        @csrf
        <input type="hidden" name="customer_id" data-payment-customer>
        <input type="hidden" name="date" value="{{ $day->format('Y-m-d') }}">
        <input type="hidden" name="form_token" value="{{ $paymentToken }}">
        @isset($backToCustomer)
            <input type="hidden" name="back_to_customer" value="{{ $backToCustomer->id }}">
        @endisset

        <div class="flex items-center gap-3">
            <span class="flex size-12 items-center justify-center rounded-full bg-credit-soft text-credit">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" /><path d="M6 9.5v.01M18 14.5v.01" /></svg>
            </span>
            <div>
                <h2 id="payment-dialog-title" class="text-xl font-extrabold">تسجيل دفعة</h2>
                <p class="text-sm text-ink-muted">من <span data-payment-name class="font-bold text-ink"></span></p>
            </div>
        </div>

        {{-- How it was paid --}}
        <fieldset class="mt-6">
            <legend class="sr-only">طريقة الدفع</legend>
            <div class="grid grid-cols-2 gap-1 rounded-xl bg-paper p-1">
                @foreach (['cash' => 'كاش', 'transfer' => 'تحويل'] as $value => $label)
                    <label class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-lg font-bold text-ink-muted transition hover:text-ink has-checked:bg-white has-checked:text-credit has-checked:shadow-sm has-focus-visible:ring-2 has-focus-visible:ring-credit/40">
                        <input type="radio" name="method" value="{{ $value }}" data-payment-method @checked($value === 'cash') class="sr-only">
                        @if ($value === 'cash')
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" /></svg>
                        @else
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3l4 4-4 4" /><path d="M3 7h18" /><path d="M7 21l-4-4 4-4" /><path d="M21 17H3" /></svg>
                        @endif
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-payment-fill="remaining" class="rounded-xl border-2 border-rule px-3 py-2.5 text-start transition hover:border-credit/50 aria-pressed:border-credit aria-pressed:bg-credit-soft">
                <span class="block text-xs font-bold text-ink-muted">شغل اليوم</span>
                <span class="text-lg font-extrabold tabular-nums"><span data-payment-remaining></span> ₪</span>
            </button>
            <button type="button" data-payment-fill="balance" class="rounded-xl border-2 border-rule px-3 py-2.5 text-start transition hover:border-credit/50 aria-pressed:border-credit aria-pressed:bg-credit-soft">
                <span class="block text-xs font-bold text-ink-muted">كل الرصيد</span>
                <span class="text-lg font-extrabold tabular-nums"><span data-payment-balance></span> ₪</span>
            </button>
        </div>

        <label for="payment-amount" class="mb-1.5 mt-5 block text-sm font-bold">المبلغ المدفوع</label>
        <div class="relative" dir="ltr">
            <input id="payment-amount" name="amount" type="text" inputmode="decimal" required autocomplete="off" data-payment-amount
                class="block h-16 w-full rounded-xl border border-rule-strong bg-white ps-4 pe-12 text-end text-3xl font-extrabold tabular-nums transition focus:border-credit focus:outline-none focus:ring-4 focus:ring-credit/15 aria-invalid:border-debit aria-invalid:focus:border-debit aria-invalid:focus:ring-debit/15">
            <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xl font-bold text-ink-muted">₪</span>
        </div>
        <p data-error-for="payment-amount" hidden class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text></span></p>

        {{-- A transfer: which account it arrived on, and its number if there is one --}}
        <div data-transfer-fields hidden class="mt-5 space-y-5">
            @if ($paymentAccounts->isEmpty())
                <p class="rounded-xl border border-dashed border-rule-strong bg-sheet px-4 py-3 text-sm leading-relaxed">
                    لا توجد حسابات تحويل بعد.
                    @can('manage-payment-accounts')
                        أضفها من <a href="{{ route('payment-accounts.index') }}" class="font-bold text-brand underline underline-offset-4">حسابات التحويل</a>.
                    @else
                        يضيفها المالك من صفحة حسابات التحويل.
                    @endcan
                </p>
                <p data-error-for="payment-account" hidden class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><span data-error-text></span></p>
            @else
                <fieldset id="payment-account" data-error-group="payment-account" tabindex="-1" class="outline-none">
                    <legend class="mb-1.5 block text-sm font-bold">وصلت على حساب</legend>
                    <div class="space-y-2">
                        @foreach ($paymentAccounts as $account)
                            <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border-2 border-rule px-3 py-2 transition hover:border-credit/50 has-checked:border-credit has-checked:bg-credit-soft has-focus-visible:ring-4 has-focus-visible:ring-credit/15">
                                <input type="radio" name="payment_account_id" value="{{ $account->id }}" data-payment-account
                                    @checked($paymentAccounts->count() === 1) class="size-4.5 shrink-0 accent-credit">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-bold">{{ $account->name }}</span>
                                    <span class="block truncate text-xs text-ink-muted">باسم {{ $account->holder_name }}</span>
                                </span>
                                <span dir="ltr" class="shrink-0 text-sm font-bold tabular-nums text-ink-muted">{{ $account->number }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p data-error-for="payment-account" hidden class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text></span></p>
                </fieldset>

                <div>
                    <label for="payment-reference" class="mb-1.5 block text-sm font-bold">رقم الحوالة <span class="font-medium text-ink-muted">(اختياري)</span></label>
                    <input id="payment-reference" name="reference" type="text" inputmode="numeric" autocomplete="off" dir="ltr" maxlength="50" data-payment-reference
                        class="block h-12 w-full rounded-xl border border-rule-strong bg-white px-4 text-end font-bold tabular-nums transition focus:border-credit focus:outline-none focus:ring-4 focus:ring-credit/15 aria-invalid:border-debit aria-invalid:focus:border-debit aria-invalid:focus:ring-debit/15">
                    <p data-error-for="payment-reference" hidden class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text></span></p>
                </div>
            @endif
        </div>

        <p data-payment-note="cash" class="mt-3 text-xs leading-relaxed text-ink-muted">الدفعة تُخصم من الرصيد الكلي، فما يزيد عن شغل اليوم يُخصم من الدين القديم.</p>
        <p data-payment-note="transfer" hidden class="mt-3 text-xs leading-relaxed text-ink-muted">تُخصم الحوالة من الرصيد فوراً، وتبقى «لم تتأكد» حتى يتحقق المالك من وصولها.</p>

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" data-payment-cancel class="inline-flex h-12 items-center justify-center rounded-xl border-2 border-rule-strong font-bold transition hover:bg-paper active:scale-[.97]">إلغاء</button>
            <button type="submit" class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-credit font-bold text-white transition hover:bg-credit/90 active:scale-[.97] aria-busy:opacity-80">
                <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                <span data-payment-submit>تسجيل الدفعة</span>
            </button>
        </div>
    </form>
</dialog>
