@php
    /** @var \App\Support\DayRow $row */
    $customer = $row->customer;
    $status = $row->status;
    $balanceSign = bccomp($row->balance, '0', 2);

    // The day's movements in order (cancelled ones struck through); a long card shows the latest five and folds the rest.
    $lines = $row->entries;
    $folded = $lines->count() > 6 ? $lines->slice(0, $lines->count() - 5) : collect();
    $shown = $lines->slice($folded->count());
@endphp

<article data-day-card data-status="{{ $status->state }}" data-search="{{ $customer->name }} {{ $customer->phone_local }}"
    data-correctable class="group/card animate-rise rounded-2xl border border-rule bg-white">
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
                <a href="{{ route('customers.show', $customer) }}" class="block truncate text-lg font-bold transition hover:text-brand">{{ $customer->name }}</a>
            @endif
            <p class="flex items-center gap-1.5 text-xs text-ink-muted">
                @if ($customer->is_one_time)
                    <x-one-time-badge />
                @endif
                آخر حركة {{ $row->lastAt->format('H:i') }}
            </p>
        </div>

        <span @class([
            'inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1 text-sm font-bold',
            'bg-credit-soft text-credit' => $status->state === \App\Support\DayStatus::PAID,
            'border border-debit/40 text-debit' => $status->state === \App\Support\DayStatus::UNPAID,
            'bg-debit-soft text-debit' => $status->state === \App\Support\DayStatus::PARTIAL,
        ])>
            @if ($status->state === \App\Support\DayStatus::PAID)
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
            @endif
            {{ $status->label() }}
        </span>
    </header>

    <div class="mx-4 mt-3 border-y border-dashed border-rule sm:mx-5">
        @if ($folded->isNotEmpty())
            <details class="group border-b border-dashed border-rule">
                <summary class="flex h-10 cursor-pointer list-none items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink [&::-webkit-details-marker]:hidden">
                    <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
                    <span class="group-open:hidden">{{ trans_choice('{1} عرض حركة سابقة|{2} عرض حركتين سابقتين|[3,10] عرض :count حركات سابقة|[11,*] عرض :count حركة سابقة', $folded->count()) }}</span>
                    <span class="hidden group-open:inline">إخفاء الحركات السابقة</span>
                </summary>
                <ul class="divide-y divide-dashed divide-rule border-t border-dashed border-rule">
                    @foreach ($folded as $line)
                        @include('day.partials.card-line')
                    @endforeach
                </ul>
            </details>
        @endif

        <ul class="divide-y divide-dashed divide-rule">
            @foreach ($shown as $line)
                @include('day.partials.card-line')
            @endforeach
        </ul>
    </div>

    <div class="px-4 sm:px-5">
        @include('day.partials.correct-toggle')
    </div>

    {{-- Totals on one line, then the actions on their own row, so every card looks the same whatever its width --}}
    <footer class="px-4 py-3.5 sm:px-5">
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
            <p class="text-sm">
                <span class="text-ink-muted">شغل اليوم</span>
                <span class="font-extrabold tabular-nums">{{ $row->work }} ₪</span>
            </p>
            @unless ($customer->is_walk_in)
                <p class="text-xs text-ink-muted">
                    الرصيد الكلي:
                    @if ($balanceSign > 0)
                        <span class="font-bold tabular-nums text-debit">عليه {{ $row->balance }} ₪</span>
                    @elseif ($balanceSign < 0)
                        <span class="font-bold tabular-nums text-credit">له {{ ltrim($row->balance, '-') }} ₪</span>
                    @else
                        <span class="font-bold">مسكّر</span>
                    @endif
                </p>
            @endunless
        </div>

        @unless ($customer->is_walk_in)
            <div class="mt-3 flex gap-2">
                <button type="button" data-entry-customer='{{ json_encode(['id' => $customer->id, 'name' => $customer->name, 'balance' => $row->balance]) }}'
                    class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-xl border border-rule-strong px-3.5 text-sm font-bold transition hover:bg-paper">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                    إضافة
                </button>

                @if ($balanceSign > 0)
                    <button type="button" data-pay='{{ json_encode(['id' => $customer->id, 'name' => $customer->name, 'remaining' => $status->remaining, 'balance' => $row->balance]) }}'
                        class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-xl bg-credit px-4 text-sm font-bold text-white transition hover:bg-credit/90 active:scale-[.97]">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                        واصل
                    </button>
                @endif
            </div>
        @endunless
    </footer>
</article>
