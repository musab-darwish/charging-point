@extends('layouts.app')

@section('title', $customer->name)

@php
    use App\Support\DayStatus;
    use App\Support\WorkDay;

    $balanceSign = bccomp($statement->balance, '0', 2);
    $today = $statement->days->first(fn ($day) => $day->date->isToday());
    $lastPaymentDay = $statement->days->first(fn ($day) => $day->payments->isNotEmpty());
    $firstDay = $statement->days->last();
@endphp

@section('content')
    <div class="space-y-6">
        <a href="{{ route('customers.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            الزبائن
        </a>

        @include('day.partials.errors')

        <div class="stagger grid items-stretch gap-4 lg:grid-cols-[minmax(0,1fr)_24rem]">
            {{-- Who they are --}}
            <header class="flex flex-col rounded-2xl border border-rule bg-white p-4 sm:p-6">
                <div class="flex items-center gap-3 sm:items-start sm:gap-4">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand text-xl font-bold text-white sm:size-16 sm:text-2xl">{{ mb_substr($customer->name, 0, 1) }}</span>

                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $customer->name }}</h1>
                        <p class="mt-1 text-ink-muted">
                            @if ($customer->phone)
                                <span dir="ltr" class="font-bold tabular-nums text-ink">{{ $customer->phone_display }}</span>
                            @else
                                بدون رقم جوال
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('customers.edit', $customer) }}" aria-label="تعديل بيانات {{ $customer->name }}"
                        class="inline-flex h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-rule-strong text-sm font-bold transition hover:bg-paper sm:px-3.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z" /><path d="M13.5 6.5l4 4" /></svg>
                        <span class="max-sm:sr-only">تعديل</span>
                    </a>
                </div>

                @if ($customer->notes)
                    <p class="mt-4 rounded-xl bg-sheet px-4 py-3 text-sm leading-relaxed">{{ $customer->notes }}</p>
                @endif

                <dl class="mt-auto grid grid-cols-2 gap-3 pt-5 text-sm max-sm:hidden">
                    <div class="rounded-xl border border-rule px-4 py-3">
                        <dt class="text-ink-muted">أول حركة</dt>
                        <dd class="mt-0.5 font-bold">{{ $firstDay ? $firstDay->date->translatedFormat('j F Y') : '—' }}</dd>
                    </div>
                    <div class="rounded-xl border border-rule px-4 py-3">
                        <dt class="text-ink-muted">أيام التعامل</dt>
                        <dd class="mt-0.5 font-bold tabular-nums">{{ $statement->days->count() }}</dd>
                    </div>
                </dl>
            </header>

            {{-- Where the account stands, ruled like the debt notebook --}}
            <section aria-labelledby="balance-title" class="ledger-paper flex flex-col rounded-2xl border border-rule py-5 ps-14 pe-5 sm:pe-6">
                <h2 id="balance-title" class="text-sm font-bold text-ink-muted">الرصيد الحالي</h2>

                <p class="mt-2 flex flex-wrap items-baseline gap-x-2">
                    @if ($balanceSign > 0)
                        <span class="text-lg font-bold text-debit">عليه</span>
                        <span class="border-b-4 border-double border-debit text-5xl font-extrabold tabular-nums text-debit">{{ $statement->balance }}</span>
                    @elseif ($balanceSign < 0)
                        <span class="text-lg font-bold text-credit">له</span>
                        <span class="border-b-4 border-double border-debit text-5xl font-extrabold tabular-nums text-credit">{{ ltrim($statement->balance, '-') }}</span>
                    @else
                        <span class="border-b-4 border-double border-debit text-5xl font-extrabold">مسكّر</span>
                    @endif
                    @if ($balanceSign !== 0)
                        <span class="text-xl font-bold text-ink-muted">₪</span>
                    @endif
                </p>

                <p class="mt-4 text-sm text-ink-muted">
                    @if ($lastPaymentDay)
                        آخر دفعة: <span class="font-bold text-credit">{{ $lastPaymentDay->payments->last()->amount }} ₪</span>
                        يوم {{ $lastPaymentDay->date->translatedFormat('l j F') }}
                    @else
                        لم يدفع أي دفعة بعد.
                    @endif
                </p>

                <div class="mt-auto flex gap-2 pt-5">
                    <button type="button" data-entry-customer='{{ json_encode(['id' => $customer->id, 'name' => $customer->name, 'balance' => $statement->balance]) }}'
                        class="inline-flex h-12 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-brand px-3 font-bold text-white transition hover:bg-brand-deep active:scale-[.97]">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                        إضافة شغل
                    </button>

                    @if ($balanceSign > 0)
                        <button type="button" data-pay='{{ json_encode([
                            'id' => $customer->id,
                            'name' => $customer->name,
                            'remaining' => $today ? DayStatus::from($today->work, $today->paid)->remaining : '0.00',
                            'balance' => $statement->balance,
                        ]) }}'
                            class="inline-flex h-12 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-credit px-3 font-bold text-white transition hover:bg-credit/90 active:scale-[.97]">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                            واصل
                        </button>
                    @endif
                </div>
            </section>
        </div>

        {{-- The statement, a day per line like the debt notebook, newest first --}}
        <section aria-labelledby="statement-title" class="animate-rise">
            <div class="mb-3 flex items-baseline justify-between gap-3">
                <h2 id="statement-title" class="text-lg font-extrabold">كشف الحساب</h2>
                @if ($statement->days->isNotEmpty())
                    <span class="text-sm text-ink-muted">الأحدث أولاً<span class="max-sm:hidden"> · اضغط على اليوم لتفاصيله</span></span>
                @endif
            </div>

            @if ($statement->days->isEmpty())
                <div class="rounded-2xl border border-dashed border-rule-strong bg-white/60 px-6 py-12 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-paper text-ink-muted">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h11l3 3v15H5z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-bold">لا توجد حركات لهذا الزبون بعد</p>
                    <p class="mt-1 text-ink-muted">سيظهر هنا كل يوم يُسجَّل له فيه شغل أو دفعة.</p>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-rule bg-white">
                    <div aria-hidden="true" class="hidden grid-cols-[8.5rem_minmax(0,1fr)_6rem_6rem_7.5rem_1.25rem] gap-x-4 border-b border-rule bg-sheet px-5 py-2.5 text-xs font-bold text-ink-muted lg:grid">
                        <span>التاريخ</span>
                        <span>البيان</span>
                        <span class="text-end">عليه</span>
                        <span class="text-end">دفعة</span>
                        <span class="text-end">الرصيد بعده</span>
                        <span></span>
                    </div>

                    <ol class="divide-y divide-rule">
                        @foreach ($statement->days as $day)
                            @php $after = bccomp($day->balanceAfter, '0', 2); @endphp

                            <li>
                                <details class="group">
                                    <summary class="grid cursor-pointer list-none grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-4 gap-y-1.5 px-4 py-3.5 transition hover:bg-sheet/70 group-open:bg-sheet/70 sm:px-5 lg:grid-cols-[8.5rem_minmax(0,1fr)_6rem_6rem_7.5rem_1.25rem] lg:items-center [&::-webkit-details-marker]:hidden">
                                        <span class="order-1">
                                            <span class="font-bold">{{ $day->date->translatedFormat('l') }}</span>
                                            @if ($day->date->isToday())
                                                <span class="ms-1 rounded-md bg-brand-soft px-1.5 py-0.5 text-xs font-bold text-brand">اليوم</span>
                                            @endif
                                            <span class="block text-xs tabular-nums text-ink-muted">{{ $day->date->format('j-n-Y') }}</span>
                                        </span>

                                        <span class="max-lg:order-3 max-lg:col-span-2 lg:order-2">
                                            @if ($day->items->isNotEmpty())
                                                {{ $day->summary() }}
                                            @else
                                                <span class="font-medium text-credit">{{ $day->payments->count() > 1 ? 'دفعات على الحساب' : 'دفعة على الحساب' }}</span>
                                            @endif
                                        </span>

                                        <span class="flex items-center gap-4 text-sm max-lg:order-4 max-lg:col-span-2 lg:contents lg:text-base">
                                            <span class="tabular-nums lg:order-3 lg:text-end">
                                                <span class="text-ink-muted lg:hidden">الشغل</span>
                                                @if (bccomp($day->work, '0', 2) > 0)
                                                    <span class="font-bold text-debit">{{ $day->work }}</span>
                                                @else
                                                    <span class="text-rule-strong">—</span>
                                                @endif
                                            </span>
                                            <span class="tabular-nums lg:order-4 lg:text-end">
                                                <span class="text-ink-muted lg:hidden">الدفعة</span>
                                                @if (bccomp($day->paid, '0', 2) > 0)
                                                    <span class="font-bold text-credit">{{ $day->paid }}</span>
                                                @else
                                                    <span class="text-rule-strong">—</span>
                                                @endif
                                            </span>
                                            <svg class="ms-auto size-4 text-ink-muted transition group-open:rotate-180 lg:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
                                        </span>

                                        <span class="text-end text-sm font-bold tabular-nums max-lg:order-2 lg:order-5">
                                            @if ($after > 0)
                                                <span class="text-debit">عليه {{ $day->balanceAfter }}</span>
                                            @elseif ($after < 0)
                                                <span class="text-credit">له {{ ltrim($day->balanceAfter, '-') }}</span>
                                            @else
                                                <span class="text-ink-muted">مسكّر</span>
                                            @endif
                                        </span>

                                        <svg class="hidden size-4 text-ink-muted transition group-open:rotate-180 lg:order-6 lg:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
                                    </summary>

                                    {{-- Every movement of the day with its time, as on the day page --}}
                                    <div class="border-t border-dashed border-rule bg-sheet/70 px-4 pb-3 sm:px-5 lg:ps-[calc(8.5rem+2.25rem)] lg:pe-[calc(7.5rem+1.25rem+3.25rem)]">
                                        <ul class="divide-y divide-dashed divide-rule">
                                            @foreach ($day->items->concat($day->payments)->sortBy([['occurred_at', 'asc'], ['id', 'asc']]) as $line)
                                                @include('day.partials.card-line')
                                            @endforeach
                                        </ul>
                                        <a href="{{ WorkDay::url($day->date) }}" class="mt-1 inline-flex h-11 items-center gap-1.5 text-sm font-bold text-brand transition hover:text-brand-deep">
                                            صفحة شغل هذا اليوم
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg>
                                        </a>
                                    </div>
                                </details>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>

        @include('day.partials.entry-form', ['day' => now()->toImmutable()->startOfDay(), 'backToCustomer' => $customer])
        @include('day.partials.payment-dialog', ['day' => now()->toImmutable()->startOfDay(), 'backToCustomer' => $customer])
    </div>
@endsection
