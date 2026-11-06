{{-- Handing an item back, with the price written now (resources/js/behaviors/freezer.js).
     The form's address is set to the item being picked up. --}}
<dialog id="freezer-pickup" aria-labelledby="freezer-pickup-title"
    data-reopen="{{ $errors->has('price') ? old('pickup_for') : '' }}"
    class="m-auto w-[min(100%-2rem,26rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <form method="POST" data-submit-lock data-pickup-form
        class="max-h-[calc(100dvh-2rem)] animate-drop overflow-y-auto rounded-3xl border border-rule bg-white p-6 shadow-2xl shadow-ink/25 sm:p-7">
        @csrf
        <input type="hidden" name="form_token" value="{{ $pickUpToken }}">
        {{-- Only so the dialog can reopen on this item if the price is refused --}}
        <input type="hidden" name="pickup_for" data-pickup-for>

        <div class="flex items-center gap-3">
            <span class="flex size-12 items-center justify-center rounded-full bg-brand-soft text-brand">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="freezer-pickup-title" class="truncate text-xl font-extrabold">تسليم <span data-pickup-label></span></h2>
                <p class="text-sm text-ink-muted">إلى <span data-pickup-customer class="font-bold text-ink"></span> · <span data-pickup-days></span></p>
            </div>
        </div>

        <label for="pickup-price" class="mb-1.5 mt-6 block text-sm font-bold">سعر التجميد</label>
        <div class="relative" dir="ltr">
            <input id="pickup-price" name="price" type="text" inputmode="decimal" required autocomplete="off" data-pickup-price value="{{ old('price') }}"
                class="block h-16 w-full rounded-xl border border-rule-strong bg-white ps-4 pe-12 text-end text-3xl font-extrabold tabular-nums transition focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
            <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xl font-bold text-ink-muted">₪</span>
        </div>
        @error('price')
            <p class="mt-2 text-sm font-medium text-debit">{{ $message }}</p>
        @else
            <p class="mt-2 text-xs text-ink-muted">اكتب 0 إن كان بدون مقابل، فلا يُسجَّل عليه شيء.</p>
        @enderror

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="submit" name="pay_now" value="0"
                class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl border-2 border-brand font-bold text-brand transition hover:bg-brand-soft active:scale-[.97] aria-busy:opacity-80">
                على الحساب
            </button>
            <button type="submit" name="pay_now" value="1"
                class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-credit font-bold text-white transition hover:bg-credit/90 active:scale-[.97] aria-busy:opacity-80">
                <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                استلام وواصل
            </button>
        </div>
        <button type="button" data-close-pickup class="mt-3 inline-flex h-11 w-full items-center justify-center rounded-xl font-bold text-ink-muted transition hover:bg-paper">إلغاء</button>
    </form>
</dialog>
