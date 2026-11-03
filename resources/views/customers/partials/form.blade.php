{{-- Shared fields for adding and editing a customer. $customer is null when adding. --}}
@php
    $prefix = old('phone_prefix', $customer?->phone_prefix ?? \App\Models\Customer::PHONE_PREFIXES[0]);
    $phoneError = $errors->first('phone_local') ?: $errors->first('phone_prefix');
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <x-input name="name" label="الاسم" :value="$customer?->name ?? $name ?? null" required autofocus autocomplete="off"
        hint="الاسم أو اللقب الذي تعرفه به، مثل: أبو محمد الطويل." />

    <div>
        <label for="phone_local" class="mb-1.5 block text-sm font-bold">رقم الجوال <span class="font-medium text-ink-muted">(اختياري)</span></label>

        <div class="flex gap-2" dir="ltr">
            <select name="phone_prefix" aria-label="مقدمة الرقم"
                class="h-13 w-24 shrink-0 rounded-xl border border-rule-strong bg-white px-3 text-lg font-bold tabular-nums text-ink transition hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
                @foreach (\App\Models\Customer::PHONE_PREFIXES as $option)
                    <option value="{{ $option }}" @selected($prefix === $option)>+{{ $option }}</option>
                @endforeach
            </select>

            <input id="phone_local" name="phone_local" type="tel" inputmode="numeric" autocomplete="off"
                value="{{ old('phone_local', $customer?->phone_local) }}" placeholder="0599123456"
                @if ($phoneError) aria-invalid="true" aria-describedby="phone_local-error" @else aria-describedby="phone_local-hint" @endif
                @class([
                    'block h-13 w-full min-w-0 rounded-xl border bg-white px-4 text-lg tabular-nums text-ink transition duration-150 placeholder:text-ink-muted/50 focus:outline-none focus:ring-4',
                    'border-rule-strong hover:border-ink-muted focus:border-brand focus:ring-brand/15' => ! $phoneError,
                    'border-debit focus:border-debit focus:ring-debit/15' => $phoneError,
                ])>
        </div>

        @if ($phoneError)
            <p id="phone_local-error" class="mt-2 flex animate-rise items-center gap-1.5 text-sm font-medium text-debit">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg>
                {{ $phoneError }}
            </p>
        @else
            <p id="phone_local-hint" class="mt-2 text-sm text-ink-muted">لزر الواتساب في رسالة الحساب. +970 لجوال وأوريدو، و+972 للأرقام الأخرى.</p>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="notes" class="mb-1.5 block text-sm font-bold">ملاحظة <span class="font-medium text-ink-muted">(اختياري)</span></label>
        <textarea id="notes" name="notes" rows="2" maxlength="500"
            @if ($errors->has('notes')) aria-invalid="true" @endif
            @class([
                'block w-full resize-y rounded-xl border bg-white px-4 py-3 text-lg text-ink transition duration-150 placeholder:text-ink-muted/50 focus:outline-none focus:ring-4',
                'border-rule-strong hover:border-ink-muted focus:border-brand focus:ring-brand/15' => ! $errors->has('notes'),
                'border-debit focus:border-debit focus:ring-debit/15' => $errors->has('notes'),
            ])
            placeholder="مثلاً: صاحب البقالة، أو يدفع آخر الشهر">{{ old('notes', $customer?->notes) }}</textarea>
        @error('notes')
            <p class="mt-2 text-sm font-medium text-debit">{{ $message }}</p>
        @enderror
    </div>
</div>
