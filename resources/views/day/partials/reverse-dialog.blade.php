{{-- Cancelling a movement entered by mistake, with the reason (resources/js/behaviors/reversal.js).
     The form's address is set to the movement being cancelled. --}}
<dialog id="reverse-dialog" aria-labelledby="reverse-dialog-title"
    data-reopen="{{ $errors->has('reason') ? old('reverse_for') : '' }}"
    class="m-auto w-[min(100%-2rem,28rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <form method="POST" data-submit-lock data-reverse-form
        class="max-h-[calc(100dvh-2rem)] animate-drop overflow-y-auto rounded-3xl border border-rule bg-white p-6 shadow-2xl shadow-ink/25 sm:p-7">
        @csrf
        <input type="hidden" name="form_token" value="{{ $reverseToken }}">
        {{-- Only so the dialog can reopen on this movement if the reason is refused --}}
        <input type="hidden" name="reverse_for" data-reverse-for>

        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-debit-soft text-debit">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="reverse-dialog-title" class="text-xl font-extrabold">إلغاء حركة</h2>
                <p class="truncate text-sm text-ink-muted"><span data-reverse-label class="font-bold text-ink"></span> · <span data-reverse-amount class="tabular-nums"></span> ₪</p>
            </div>
        </div>

        <p class="mt-5 rounded-xl bg-sheet px-4 py-3 text-sm leading-relaxed text-ink-muted">
            لا تُحذف الحركة: تُضاف حركة معاكسة بالمبلغ نفسه في يومها، فيُصحَّح الرصيد، وتبقى الحركة ظاهرة مشطوبة مع سبب إلغائها.
        </p>

        <label for="reverse-reason" class="mb-1.5 mt-5 block text-sm font-bold">سبب الإلغاء</label>
        <input id="reverse-reason" name="reason" type="text" maxlength="150" autocomplete="off" value="{{ old('reason') }}"
            @error('reason') aria-invalid="true" @enderror
            class="block h-13 w-full rounded-xl border border-rule-strong bg-white px-4 text-lg transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-debit focus:outline-none focus:ring-4 focus:ring-debit/15 aria-invalid:border-debit">
        <p data-error-for="reverse-reason" @unless ($errors->has('reason')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text>{{ $errors->first('reason') }}</span></p>

        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach (['سُجّلت بالخطأ', 'سُجّلت لزبون آخر', 'المبلغ خطأ', 'مكرّرة'] as $reason)
                <button type="button" data-reverse-reason="{{ $reason }}"
                    class="inline-flex h-11 items-center rounded-lg border border-rule px-3 text-sm font-bold text-ink-muted transition hover:border-debit/40 hover:bg-debit-soft hover:text-debit">{{ $reason }}</button>
            @endforeach
            <button type="button" data-reverse-reason="لم تصل الحوالة" data-transfer-only hidden
                class="inline-flex h-11 items-center rounded-lg border border-rule px-3 text-sm font-bold text-ink-muted transition hover:border-debit/40 hover:bg-debit-soft hover:text-debit">لم تصل الحوالة</button>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" data-close-reverse class="inline-flex h-12 items-center justify-center rounded-xl border-2 border-rule-strong font-bold transition hover:bg-paper active:scale-[.97]">تراجع</button>
            <button type="submit" class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-debit font-bold text-white transition hover:bg-debit/90 active:scale-[.97] aria-busy:opacity-80">
                <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                إلغاء الحركة
            </button>
        </div>
    </form>
</dialog>
