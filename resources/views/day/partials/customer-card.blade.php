@php
    /** @var \App\Support\DayRow $row */
    $customer = $row->customer;
    $status = $row->status;
    $balanceSign = bccomp($row->balance, '0', 2);
@endphp

<article class="mb-3 animate-rise rounded-2xl border border-rule bg-white">
    <header class="flex items-center gap-3 px-4 pt-4 sm:px-5">
        <span @class([
            'flex size-11 shrink-0 items-center justify-center rounded-full text-base font-bold',
            'bg-paper text-ink-muted' => $customer->is_walk_in,
            'bg-brand text-white' => ! $customer->is_walk_in,
        ])>{{ mb_substr($customer->name, 0, 1) }}</span>

        <div class="min-w-0 flex-1">
            @if ($customer->is_walk_in)
                <p class="truncate text-lg font-bold">{{ $customer->name }}</p>
            @else
                <a href="{{ route('customers.edit', $customer) }}" class="block truncate text-lg font-bold transition hover:text-brand">{{ $customer->name }}</a>
            @endif
            <p class="text-xs text-ink-muted">آخر حركة {{ $row->lastAt->format('H:i') }}</p>
        </div>

        <span @class([
            'inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1 text-sm font-bold',
            'bg-brand-soft text-brand' => $status->state === \App\Support\DayStatus::PAID,
            'border border-debit/40 text-debit' => $status->state === \App\Support\DayStatus::UNPAID,
            'bg-debit-soft text-debit' => $status->state === \App\Support\DayStatus::PARTIAL,
        ])>
            @if ($status->state === \App\Support\DayStatus::PAID)
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
            @endif
            {{ $status->label() }}
        </span>
    </header>

    <ul class="mx-4 mt-3 divide-y divide-dashed divide-rule border-y border-dashed border-rule sm:mx-5">
        @foreach ($row->items as $item)
            <li class="flex items-baseline gap-3 py-2">
                <span class="w-10 shrink-0 text-xs tabular-nums text-ink-muted">{{ $item->occurred_at->format('H:i') }}</span>
                <span class="min-w-0 flex-1 truncate">
                    {{ $item->description }}
                    @if ($item->quantity > 1)
                        <span class="text-sm font-bold text-ink-muted">×{{ $item->quantity }}</span>
                    @endif
                </span>
                <span class="font-bold tabular-nums">{{ $item->amount }}</span>
            </li>
        @endforeach

        @foreach ($row->payments as $payment)
            <li class="flex items-baseline gap-3 py-2 text-credit">
                <span class="w-10 shrink-0 text-xs tabular-nums opacity-70">{{ $payment->occurred_at->format('H:i') }}</span>
                <span class="min-w-0 flex-1 font-medium">دفعة {{ $payment->method === 'cash' ? 'نقدية' : 'بتحويل' }}</span>
                <span class="font-bold tabular-nums">−{{ $payment->amount }}</span>
            </li>
        @endforeach
    </ul>

    <footer class="flex flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3.5 sm:px-5">
        <div class="me-auto">
            <p class="text-sm">
                <span class="text-ink-muted">شغل اليوم</span>
                <span class="font-extrabold tabular-nums">{{ $row->work }} ₪</span>
            </p>
            @unless ($customer->is_walk_in)
                <p class="text-xs text-ink-muted">
                    الرصيد الكلي:
                    @if ($balanceSign > 0)
                        <span class="font-bold text-debit">عليه {{ $row->balance }} ₪</span>
                    @elseif ($balanceSign < 0)
                        <span class="font-bold text-credit">له {{ ltrim($row->balance, '-') }} ₪</span>
                    @else
                        <span class="font-bold">مسكّر</span>
                    @endif
                </p>
            @endunless
        </div>

        @unless ($customer->is_walk_in)
            <button type="button" data-entry-customer='{{ json_encode(['id' => $customer->id, 'name' => $customer->name, 'balance' => $row->balance]) }}'
                class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-rule-strong px-3.5 text-sm font-bold transition hover:bg-paper">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                إضافة
            </button>

            @if ($balanceSign > 0)
                <button type="button" data-pay='{{ json_encode(['id' => $customer->id, 'name' => $customer->name, 'remaining' => $status->remaining, 'balance' => $row->balance]) }}'
                    class="inline-flex h-11 items-center gap-1.5 rounded-xl bg-credit px-4 text-sm font-bold text-white transition hover:bg-credit/90 active:scale-[.97]">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                    واصل
                </button>
            @endif
        @endunless
    </footer>
</article>
