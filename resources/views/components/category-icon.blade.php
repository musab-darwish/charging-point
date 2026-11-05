@props(['category'])

{{-- One icon per kind of item: charging, freezing, drink, other --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($category)
        @case('charging')
            <path d="M13 3 5 14h6l-1 7 8-11h-6z" />
            @break
        @case('freezing')
            <path d="M12 3v18M4.2 7.5l15.6 9M4.2 16.5l15.6-9" />
            <path d="M9.5 4.5 12 7l2.5-2.5M9.5 19.5 12 17l2.5 2.5" />
            @break
        @case('drink')
            <path d="M6 4h12l-1.5 16h-9z" />
            <path d="M6.5 9h11" />
            @break
        @default
            <circle cx="6" cy="12" r="1.5" />
            <circle cx="12" cy="12" r="1.5" />
            <circle cx="18" cy="12" r="1.5" />
    @endswitch
</svg>
