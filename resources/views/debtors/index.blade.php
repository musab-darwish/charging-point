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
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">المديونون</h1>
                <p class="mt-2 text-ink-muted">كل من عليه رصيد، الأعلى أولاً، ومتى دفع آخر مرة.</p>
            </div>

            @can('export-debtors')
                @if ($debtors->owing->isNotEmpty() || $debtors->ahead->isNotEmpty())
                    <a href="{{ route('debtors.export') }}" download
                        class="inline-flex h-13 shrink-0 items-center justify-center gap-2 rounded-xl border-2 border-ink px-5 font-bold text-ink transition duration-150 hover:bg-paper active:scale-[.97]">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6" /><path d="M9 13l6 5M15 13l-6 5" /></svg>
                        تصدير Excel
                    </a>
                @endif
            @endcan
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

                        <li class="flex items-center pe-2 transition hover:bg-sheet sm:pe-3">
                            <a href="{{ route('customers.show', $row->customer) }}"
                                class="group flex min-w-0 flex-1 items-center gap-3 py-3.5 ps-4 pe-2 sm:gap-4 sm:ps-5">
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
                            </a>
                            <button type="button" data-account-message="{{ route('customers.message', $row->customer) }}"
                                aria-label="رسالة حساب {{ $row->customer->name }}" title="رسالة الحساب"
                                class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-rule-strong bg-white text-brand transition hover:border-brand/40 hover:bg-brand-soft">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z" /><path d="M8.5 10.5h7M8.5 14h4" /></svg>
                            </button>
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

        @include('partials.account-message-dialog')
    </div>
@endsection
