@props(['category'])

{{-- One icon per kind of item: charging, cooling, drink, other --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($category)
        @case('charging')
            <path d="M13 3 5 14h6l-1 7 8-11h-6z" />
            @break
        @case('cooling')
            {{-- A cold thermometer, so it isn't confused with the freezer's snowflake --}}
            <path d="M10 13.5V5a2 2 0 1 1 4 0v8.5a4 4 0 1 1-4 0z" />
            <path d="M12 9v7" />
            <path d="M17.5 5h3M17.5 9h3" />
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
