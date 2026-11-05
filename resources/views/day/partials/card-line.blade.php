{{-- One movement on a customer's card: an item, or a payment in blue --}}
@if ($line->isDebit())
    <li class="flex items-baseline gap-3 py-2">
        <span class="w-10 shrink-0 text-xs tabular-nums text-ink-muted">{{ $line->occurred_at->format('H:i') }}</span>
        <span class="min-w-0 flex-1 truncate">
            {{ $line->description }}
            @if ($line->quantity > 1)
                <span class="text-sm font-bold text-ink-muted">×{{ $line->quantity }}</span>
            @endif
        </span>
        <span class="font-bold tabular-nums">{{ $line->amount }}</span>
    </li>
@else
    <li class="flex items-baseline gap-3 py-2 text-credit">
        <span class="w-10 shrink-0 text-xs tabular-nums opacity-70">{{ $line->occurred_at->format('H:i') }}</span>
        <span class="min-w-0 flex-1 font-medium">دفعة {{ $line->method === 'cash' ? 'نقدية' : 'بتحويل' }}</span>
        <span class="font-bold tabular-nums">−{{ $line->amount }}</span>
    </li>
@endif
