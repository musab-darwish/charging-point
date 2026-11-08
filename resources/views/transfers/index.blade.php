@extends('layouts.app')

@section('title', 'الحوالات')

@php
    $ago = fn (int $days) => match (true) {
        $days === 0 => 'اليوم',
        $days === 1 => 'أمس',
        default => 'قبل '.trans_choice('{2} يومين|[3,10] :count أيام|[11,*] :count يوماً', $days),
    };
@endphp

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-3xl font-extrabold">الحوالات</h1>
            <p class="mt-2 max-w-2xl text-ink-muted">حوالات سُجّلت ولم يُتأكد من وصولها بعد، مجمّعة بالحساب. افتح التطبيق وراجعها واحدة واحدة.</p>
        </header>

        <dl class="stagger grid grid-cols-2 gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-rule bg-white p-4">
                <dt class="text-sm font-bold text-ink-muted">لم تتأكد</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $count }}</dd>
            </div>
            <div class="rounded-2xl border border-rule bg-white p-4">
                <dt class="text-sm font-bold text-ink-muted">مجموعها</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums text-credit">{{ $total }} <span class="text-base">₪</span></dd>
            </div>
        </dl>

        @if ($groups->isEmpty())
            <div class="animate-rise rounded-2xl border border-dashed border-rule-strong bg-white/60 px-6 py-12 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-soft text-brand">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                </span>
                <p class="mt-4 text-lg font-bold">كل الحوالات مؤكدة</p>
                <p class="mt-1 text-ink-muted">ستظهر هنا كل حوالة تُسجَّل حتى يُتأكد من وصولها.</p>
            </div>
        @else
            <div class="grid items-start gap-4 lg:grid-cols-2">
                @foreach ($groups as $group)
                    <section class="animate-rise overflow-hidden rounded-2xl border border-rule bg-white" aria-label="حوالات {{ $group['account']?->name }}">
                        <header class="flex items-center gap-3 border-b border-rule bg-sheet/60 px-4 py-3.5 sm:px-5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-credit-soft text-credit">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h4" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-lg font-extrabold">{{ $group['account']?->name }}</h2>
                                <p class="truncate text-xs text-ink-muted">باسم {{ $group['account']?->holder_name }} · <span dir="ltr" class="tabular-nums">{{ $group['account']?->number }}</span></p>
                            </div>
                            <div class="shrink-0 text-end">
                                <p class="font-extrabold tabular-nums text-credit">{{ $group['total'] }} ₪</p>
                                <p class="text-xs text-ink-muted">{{ trans_choice('{1} حوالة|{2} حوالتان|[3,10] :count حوالات|[11,*] :count حوالة', $group['transfers']->count()) }}</p>
                            </div>
                        </header>

                        <ul class="divide-y divide-dashed divide-rule">
                            @foreach ($group['transfers'] as $transfer)
                                @php $days = (int) $transfer->occurred_at->copy()->startOfDay()->diffInDays(today()); @endphp

                                <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5">
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('customers.show', $transfer->customer) }}" class="block truncate font-bold transition hover:text-brand">{{ $transfer->customer->name }}</a>
                                        <p class="text-xs text-ink-muted">
                                            {{ $transfer->occurred_at->translatedFormat('l j F، H:i') }}
                                            · <span @class(['font-bold text-debit' => $days >= 3])>{{ $ago($days) }}</span>
                                            @if ($transfer->reference)
                                                · رقم <span dir="ltr" class="font-bold tabular-nums">{{ $transfer->reference }}</span>
                                            @endif
                                            · سجّلها {{ $transfer->user?->name }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 text-lg font-extrabold tabular-nums text-credit">{{ $transfer->amount }} ₪</span>

                                    @can('verify-transfers')
                                        <form method="POST" action="{{ route('transfers.verify', $transfer) }}" data-submit-lock data-stay class="shrink-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-credit/40 px-3.5 text-sm font-bold text-credit transition hover:bg-credit-soft active:scale-[.97]">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                                                تأكدت
                                            </button>
                                        </form>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            @cannot('verify-transfers')
                <p class="text-sm text-ink-muted">يؤكد المالك وصول الحوالات بعد مراجعة التطبيق.</p>
            @endcannot
        @endif
    </div>
@endsection
