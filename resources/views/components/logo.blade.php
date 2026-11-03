@props(['animated' => false])

{{-- The mark: the letter nun from "نقطة", its dot in the debit red. Size and stroke color come from the caller's classes. --}}
<svg viewBox="0 0 64 64" aria-hidden="true" {{ $attributes->merge(['class' => 'shrink-0 text-ink']) }}>
    <path d="M14 27v5a18 18 0 0 0 36 0v-5" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round"
        @if ($animated) pathLength="1" stroke-dasharray="1" class="animate-draw" @endif />
    <circle cx="32" cy="14" r="6.5" @class(['fill-debit', 'animate-pop origin-center [transform-box:fill-box]' => $animated]) />
</svg>
