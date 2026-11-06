@php
    $oldCustomer = old('customer_id') ? \App\Models\Customer::find(old('customer_id')) : null;
    $old = [
        'customer' => $oldCustomer ? ['id' => $oldCustomer->id, 'name' => $oldCustomer->name, 'balance' => $oldCustomer->balance()] : null,
        'newCustomer' => old('new_customer_name'),
    ];
@endphp

{{-- Putting something in the freezer (resources/js/behaviors/freezer.js) --}}
<dialog id="freezer-store" aria-labelledby="freezer-store-title"
    data-open-on-load="{{ $errors->hasAny(['customer_id', 'new_customer_name', 'description', 'quantity', 'stored_on']) ? 'true' : 'false' }}"
    class="m-auto w-[min(100%-2rem,30rem)] overflow-visible bg-transparent p-0 text-ink backdrop:bg-ink/45">
    <form method="POST" action="{{ route('freezer.store') }}" data-submit-lock data-freezer-store-form data-old='{{ json_encode($old) }}'
        class="max-h-[calc(100dvh-2rem)] animate-drop overflow-y-auto rounded-3xl border border-rule bg-white p-6 shadow-2xl shadow-ink/25 sm:p-7">
        @csrf
        <input type="hidden" name="form_token" value="{{ $storeToken }}">
        <input type="hidden" name="customer_id" data-customer-id>
        <input type="hidden" name="new_customer_name" data-new-customer>

        <div class="flex items-center gap-3">
            <span class="flex size-12 items-center justify-center rounded-full bg-brand-soft text-brand">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20M4.9 6l14.2 12M4.9 18L19.1 6" /><path d="M9 3.5l3 2 3-2M9 20.5l3-2 3 2" /></svg>
            </span>
            <div>
                <h2 id="freezer-store-title" class="text-xl font-extrabold">وضع غرض في الفريزر</h2>
                <p class="text-sm text-ink-muted">لا يُسجَّل عليه شيء حتى الاستلام.</p>
            </div>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <p class="mb-1.5 text-sm font-bold">لمن؟</p>
                @include('partials.customer-picker', ['pickerId' => 'freezer', 'next' => '#freezer-description'])
                <p data-error-for="freezer-customer-search" @unless ($errors->hasAny(['customer_id', 'new_customer_name'])) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text>{{ $errors->first('customer_id') ?: $errors->first('new_customer_name') }}</span></p>
            </div>

            <div>
                <label for="freezer-description" class="mb-1.5 block text-sm font-bold">ما الغرض؟</label>
                <input id="freezer-description" name="description" type="text" required maxlength="150" autocomplete="off" value="{{ old('description') }}"
                    placeholder="مثل: دجاجة" data-freezer-description @error('description') aria-invalid="true" @enderror
                    class="block h-13 w-full rounded-xl border border-rule-strong bg-white px-4 text-lg transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15 aria-invalid:border-debit aria-invalid:focus:border-debit aria-invalid:focus:ring-debit/15">
                <p data-error-for="freezer-description" @unless ($errors->has('description')) hidden @endunless class="mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text>{{ $errors->first('description') }}</span></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="freezer-quantity" class="mb-1.5 block text-sm font-bold">الكمية <span class="font-medium text-ink-muted">(اختياري)</span></label>
                    <input id="freezer-quantity" name="quantity" type="text" inputmode="numeric" autocomplete="off" value="{{ old('quantity') }}" placeholder="1" dir="ltr" @error('quantity') aria-invalid="true" @enderror
                        class="block h-13 w-full rounded-xl border border-rule-strong bg-white px-4 text-end text-lg font-bold tabular-nums transition placeholder:text-ink-muted/50 hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15 aria-invalid:border-debit aria-invalid:focus:border-debit aria-invalid:focus:ring-debit/15">
                </div>
                <div>
                    <label for="freezer-stored-on" class="mb-1.5 block text-sm font-bold">تاريخ الوصول</label>
                    <input id="freezer-stored-on" name="stored_on" type="date" required value="{{ old('stored_on', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" @error('stored_on') aria-invalid="true" @enderror
                        class="block h-13 w-full rounded-xl border border-rule-strong bg-white px-3 font-bold tabular-nums transition hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15 aria-invalid:border-debit aria-invalid:focus:border-debit aria-invalid:focus:ring-debit/15">
                </div>
            </div>
            <p data-error-for="freezer-quantity" @unless ($errors->has('quantity')) hidden @endunless class="-mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text>{{ $errors->first('quantity') }}</span></p>
            <p data-error-for="freezer-stored-on" @unless ($errors->has('stored_on')) hidden @endunless class="-mt-2 flex items-center gap-1.5 text-sm font-medium text-debit"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg><span data-error-text>{{ $errors->first('stored_on') }}</span></p>
            <p data-hint-for="freezer-stored-on" @error('stored_on') hidden @enderror class="-mt-2 text-xs text-ink-muted">اختر يوماً سابقاً للأغراض الموجودة في الفريزر من قبل، ليُحسب عدد أيامها صحيحاً.</p>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" data-close-freezer class="inline-flex h-12 items-center justify-center rounded-xl border-2 border-rule-strong font-bold transition hover:bg-paper active:scale-[.97]">إلغاء</button>
            <button type="submit" class="group inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-brand font-bold text-white transition hover:bg-brand-deep active:scale-[.97] aria-busy:opacity-80">
                <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                وضع في الفريزر
            </button>
        </div>
    </form>
</dialog>
