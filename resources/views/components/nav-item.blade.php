@props(['href' => null, 'active' => false, 'soon' => false, 'layout' => 'side', 'count' => null])

@php
    $tag = $soon ? 'span' : 'a';
    $side = $layout === 'side';
@endphp

<{{ $tag }}
    @unless ($soon) href="{{ $href }}" @endunless
    @if ($active) aria-current="page" @endif
    @if ($soon) aria-disabled="true" title="قريباً" @endif
    {{ $attributes->class([
        'relative flex items-center transition duration-150',
        'h-11 gap-3 rounded-xl px-3 text-[15px]' => $side,
        'h-full flex-col justify-center gap-1 pt-1 text-xs' => ! $side,
        'bg-brand font-bold text-white' => $side && $active,
        'font-medium text-ink hover:bg-paper' => $side && ! $active && ! $soon,
        'font-bold text-brand' => ! $side && $active,
        'font-medium text-ink-muted' => ! $side && ! $active && ! $soon,
        'cursor-default font-medium text-ink-muted/60' => $soon,
    ]) }}>
    @unless ($side)
        <span @class(['size-1.5 rounded-full', 'bg-debit' => $active])></span>
    @endunless

    <span class="relative flex size-6 items-center justify-center">
        {{ $icon }}
        @if ($count && ! $side)
            <span class="absolute -top-1.5 -end-2.5 min-w-4.5 rounded-full bg-brand px-1 text-center text-[11px] font-bold leading-4.5 tabular-nums text-white">{{ $count }}</span>
        @endif
    </span>
    <span>{{ $slot }}</span>

    @if ($count && $side)
        <span @class([
            'ms-auto rounded-md px-1.5 text-xs font-bold tabular-nums',
            'bg-white/20 text-white' => $active,
            'bg-paper text-ink-muted' => ! $active,
        ])>{{ $count }}</span>
    @endif

    @if ($side && $active)
        <span class="ms-auto size-1.5 rounded-full bg-debit"></span>
    @elseif ($side && $soon)
        <span class="ms-auto rounded-md border border-rule-strong px-1.5 text-[11px] font-bold">قريباً</span>
    @endif
</{{ $tag }}>
