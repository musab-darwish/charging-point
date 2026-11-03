@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'keepOld' => true])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $describedBy = $hasError ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
    $isPassword = $type === 'password';
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-bold">{{ $label }}</label>

    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
            @unless ($isPassword) value="{{ $keepOld ? old($name, $value) : $value }}" @endunless
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('id')->class([
                'block h-13 w-full rounded-xl border bg-white px-4 text-lg text-ink transition duration-150',
                'placeholder:text-ink-muted/60 focus:outline-none focus:ring-4',
                'border-rule-strong hover:border-ink-muted focus:border-ink focus:ring-ink/10' => ! $hasError,
                'border-debit focus:border-debit focus:ring-debit/15' => $hasError,
                'pe-13' => $isPassword,
            ]) }}>

        @if ($isPassword)
            <button type="button" data-password-toggle="{{ $id }}" aria-label="إظهار كلمة المرور" aria-pressed="false"
                class="group absolute inset-y-0 end-0 flex w-13 items-center justify-center rounded-e-xl text-ink-muted transition hover:text-ink">
                <svg class="size-5 group-aria-pressed:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <svg class="hidden size-5 group-aria-pressed:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 3l18 18" />
                    <path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6C3.8 8.4 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6" />
                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                </svg>
            </button>
        @endif
    </div>

    @if ($hasError)
        <p id="{{ $id }}-error" class="mt-2 flex animate-rise items-center gap-1.5 text-sm font-medium text-debit">
            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7.5v5.5M12 16.5v.01" />
            </svg>
            {{ $errors->first($name) }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-2 text-sm text-ink-muted">{{ $hint }}</p>
    @endif
</div>
