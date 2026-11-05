{{-- "واصل": a cash payment for one customer, opened from their card (see resources/js/behaviors/payment-dialog.js) --}}
<dialog id="payment-dialog" aria-labelledby="payment-dialog-title"
    class="m-auto w-[min(100%-2rem,26rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <form method="POST" action="{{ route('payments.cash') }}" data-submit-lock class="animate-drop rounded-3xl border border-rule bg-white p-6 shadow-2xl shadow-ink/25 sm:p-7">
        @csrf
        <input type="hidden" name="customer_id" data-payment-customer>
        <input type="hidden" name="date" value="{{ $sheet->day->format('Y-m-d') }}">
        <input type="hidden" name="form_token" value="{{ $paymentToken }}">

        <div class="flex items-center gap-3">
            <span class="flex size-12 items-center justify-center rounded-full bg-credit-soft text-credit">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2" /><circle cx="12" cy="12" r="2.5" /><path d="M6 9.5v.01M18 14.5v.01" /></svg>
            </span>
            <div>
                <h2 id="payment-dialog-title" class="text-xl font-extrabold">دفعة نقدية</h2>
                <p class="text-sm text-ink-muted">من <span data-payment-name class="font-bold text-ink"></span></p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-2">
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
                class="block h-16 w-full rounded-xl border border-rule-strong bg-white ps-4 pe-12 text-end text-3xl font-extrabold tabular-nums transition focus:border-credit focus:outline-none focus:ring-4 focus:ring-credit/15">
            <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xl font-bold text-ink-muted">₪</span>
        </div>
        <p class="mt-2 text-xs text-ink-muted">الدفعة تُخصم من الرصيد الكلي، فما يزيد عن شغل اليوم يُخصم من الدين القديم.</p>

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" data-payment-cancel class="inline-flex h-12 items-center justify-center rounded-xl border-2 border-rule-strong font-bold transition hover:bg-paper active:scale-[.97]">إلغاء</button>
            <button type="submit" class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-credit font-bold text-white transition hover:bg-credit/90 active:scale-[.97] aria-busy:opacity-80">
                <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                تسجيل الدفعة
            </button>
        </div>
    </form>
</dialog>
