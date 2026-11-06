@extends('layouts.app')

@section('title', 'المديونون')

@php
    // "قبل 12 يوماً", with today and yesterday in words.
    $since = fn (?int $days) => match (true) {
        $days === null => null,
        $days === 0 => 'اليوم',
        $days === 1 => 'أمس',
        default => 'قبل '.trans_choice('{2} يومين|[3,10] :count أيام|[11,*] :count يوماً', $days),
    };
@endphp

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-3xl font-extrabold">المديونون</h1>
            <p class="mt-2 text-ink-muted">كل من عليه رصيد، الأعلى أولاً، ومتى دفع آخر مرة.</p>
        </header>

        <div class="stagger grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            {{-- The total, ruled like the bottom line of the debt notebook --}}
            <section aria-labelledby="total-title" class="ledger-paper rounded-2xl border border-rule py-5 ps-14 pe-5">
                <h2 id="total-title" class="text-sm font-bold text-ink-muted">مجموع الديون</h2>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="border-b-4 border-double border-debit text-5xl font-extrabold tabular-nums text-debit">{{ $debtors->totalOwing }}</span>
                    <span class="text-xl font-bold text-ink-muted">₪</span>
                </p>
                <p class="mt-3 text-sm text-ink-muted">على {{ trans_choice('{0} لا أحد|{1} زبون واحد|{2} زبونين|[3,10] :count زبائن|[11,*] :count زبوناً', $debtors->owing->count()) }}</p>
            </section>

            <div class="rounded-2xl border border-rule bg-white p-5">
                <p class="text-sm font-bold text-ink-muted">لهم رصيد</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-credit">{{ $debtors->totalAhead }} <span class="text-base">₪</span></p>
                <p class="mt-3 text-sm text-ink-muted">{{ trans_choice('{0} لا أحد دفع زيادة|{1} زبون واحد دفع زيادة|{2} زبونان دفعا زيادة|[3,10] :count زبائن دفعوا زيادة|[11,*] :count زبوناً دفعوا زيادة', $debtors->ahead->count()) }}</p>
            </div>
        </div>

        <section aria-labelledby="owing-title" class="animate-rise">
            <h2 id="owing-title" class="mb-3 text-lg font-extrabold">عليهم <span class="text-sm font-medium text-ink-muted">الأعلى أولاً</span></h2>

            @if ($debtors->owing->isEmpty())
                <div class="rounded-2xl border border-dashed border-rule-strong bg-white/60 px-6 py-12 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-soft text-brand">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-bold">لا ديون على أحد</p>
                    <p class="mt-1 text-ink-muted">كل الحسابات مسكّرة.</p>
                </div>
            @else
                <ol class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
                    @foreach ($debtors->owing as $row)
                        @php $days = $row->daysSincePayment(); @endphp

                        <li>
                            <a href="{{ route('customers.show', $row->customer) }}"
                                class="group flex items-center gap-3 px-4 py-3.5 transition hover:bg-sheet sm:gap-4 sm:px-5">
                                <span class="w-6 shrink-0 text-center text-sm font-bold tabular-nums text-ink-muted">{{ $loop->iteration }}</span>
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">{{ mb_substr($row->customer->name, 0, 1) }}</span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-bold transition group-hover:text-brand">{{ $row->customer->name }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-ink-muted">
                                        @if ($row->lastPayment)
                                            آخر دفعة <span class="font-bold tabular-nums text-credit">{{ $row->lastPayment->amount }} ₪</span>
                                            <span @class(['font-bold text-debit' => $days >= 30])>{{ $since($days) }}</span>
                                        @else
                                            <span class="font-bold text-debit">لم يدفع بعد</span>
                                        @endif
                                    </span>
                                </span>

                                <span class="shrink-0 text-lg font-extrabold tabular-nums text-debit">{{ $row->balance }} <span class="text-sm">₪</span></span>
                                <svg class="size-4 shrink-0 text-rule-strong transition group-hover:-translate-x-1 group-hover:text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        @if ($debtors->ahead->isNotEmpty())
            <section aria-labelledby="ahead-title" class="animate-rise max-lg:pb-20">
                <h2 id="ahead-title" class="mb-3 text-lg font-extrabold">لهم رصيد <span class="text-sm font-medium text-ink-muted">يُخصم من شغلهم القادم</span></h2>

                <ul class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
                    @foreach ($debtors->ahead as $row)
                        <li>
                            <a href="{{ route('customers.show', $row->customer) }}" class="group flex items-center gap-3 px-4 py-3.5 transition hover:bg-sheet sm:gap-4 sm:px-5">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-credit-soft text-sm font-bold text-credit">{{ mb_substr($row->customer->name, 0, 1) }}</span>
                                <span class="min-w-0 flex-1 truncate font-bold transition group-hover:text-brand">{{ $row->customer->name }}</span>
                                <span class="shrink-0 text-lg font-extrabold tabular-nums text-credit">له {{ ltrim($row->balance, '-') }} <span class="text-sm">₪</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
