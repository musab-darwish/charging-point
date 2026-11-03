@props(['variant' => 'primary', 'type' => 'submit', 'loading' => null])

@php
    $variants = [
        'primary' => 'bg-brand text-white hover:bg-brand-deep',
        'secondary' => 'border-2 border-ink text-ink hover:bg-paper',
        'credit' => 'bg-credit text-white hover:bg-credit/90',
        'debit' => 'bg-debit text-white hover:bg-debit/90',
        'ghost' => 'text-ink hover:bg-paper',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->class([
    'group inline-flex h-13 select-none items-center justify-center gap-2 rounded-xl px-5 text-base font-bold',
    'transition duration-150 ease-out active:scale-[.97] disabled:pointer-events-none disabled:opacity-50',
    'aria-busy:pointer-events-none aria-busy:opacity-85',
    $variants[$variant],
]) }}>
    <svg class="hidden size-5 animate-spin-fast group-aria-busy:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
        <path d="M12 3a9 9 0 1 0 9 9" />
    </svg>

    @if ($loading)
        <span class="group-aria-busy:hidden">{{ $slot }}</span>
        <span class="hidden group-aria-busy:inline">{{ $loading }}</span>
    @else
        {{ $slot }}
    @endif
</button>
