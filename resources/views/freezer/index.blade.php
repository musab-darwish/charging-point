@extends('layouts.app')

@section('title', 'التجميد')

@php
    $dayCount = fn (int $count) => trans_choice('{1} يوم واحد|{2} يومان|[3,10] :count أيام|[11,*] :count يوماً', $count);
    $days = fn (int $count) => $count === 0 ? 'وُضع اليوم' : $dayCount($count);
    $stayed = fn (int $count) => $count === 0 ? 'أقل من يوم' : $dayCount($count);
    $oldest = $items->first();
@endphp

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">التجميد</h1>
                <p class="mt-2 max-w-2xl text-ink-muted">ما في الفريزر الآن، الأقدم أولاً. لا يُسجَّل على الزبون شيء حتى يستلم غرضه ويُكتب سعره.</p>
            </div>

            <button type="button" data-open-freezer
                class="inline-flex h-13 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                وضع غرض في الفريزر
            </button>
        </header>

        @include('day.partials.errors')

        <dl class="stagger grid grid-cols-2 gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-rule bg-white p-4">
                <dt class="text-sm font-bold text-ink-muted">في الفريزر</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $items->count() }}</dd>
            </div>
            <div class="rounded-2xl border border-rule bg-white p-4">
                <dt class="text-sm font-bold text-ink-muted">أقدم غرض</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums">
                    @if ($oldest)
                        {{ $oldest->daysStored() }} <span class="text-base text-ink-muted">{{ $oldest->daysStored() === 1 ? 'يوم' : 'أيام' }}</span>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="col-span-2 rounded-2xl border border-rule bg-white p-4 lg:col-span-1">
                <dt class="text-sm font-bold text-ink-muted">استُلم هذا الأسبوع</dt>
                <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $recent->count() }}</dd>
            </div>
        </dl>

        {{-- What is waiting, a card each --}}
        <section aria-labelledby="freezer-now-title" class="animate-rise max-lg:pb-4">
            <h2 id="freezer-now-title" class="mb-3 text-lg font-extrabold">في الفريزر الآن</h2>

            @if ($items->isEmpty())
                <div class="ledger-paper rounded-2xl border border-rule py-14 ps-14 pe-6 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-ink-muted">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20M4.9 6l14.2 12M4.9 18L19.1 6" /><path d="M9 3.5l3 2 3-2M9 20.5l3-2 3 2" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-bold">الفريزر فارغ</p>
                    <p class="mt-1 text-ink-muted">سجّل كل غرض يوضع في الفريزر، ليظهر هنا حتى يستلمه صاحبه.</p>
                </div>
            @else
                <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($items as $item)
                        @php $stored = $item->daysStored(); @endphp

                        <li class="flex flex-col rounded-2xl border border-rule bg-white p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-lg font-extrabold leading-snug">{{ $item->label() }}</p>
                                    <a href="{{ route('customers.show', $item->customer) }}" class="mt-0.5 inline-flex min-h-11 items-center font-bold text-brand transition hover:text-brand-deep">{{ $item->customer->name }}</a>
                                </div>

                                <span @class([
                                    'shrink-0 rounded-lg px-2.5 py-1 text-sm font-bold tabular-nums',
                                    'bg-brand-soft text-brand' => $stored === 0,
                                    'bg-paper text-ink' => $stored > 0 && $stored < 7,
                                    'bg-debit-soft text-debit' => $stored >= 7,
                                ])>{{ $days($stored) }}</span>
                            </div>

                            <p class="text-xs text-ink-muted">
                                وصل {{ $item->stored_at->translatedFormat('l j F') }} · سجّله {{ $item->user->name }}
                            </p>

                            <div class="mt-4 flex items-center gap-2 border-t border-dashed border-rule pt-4">
                                <button type="button" data-pickup='{{ json_encode([
                                    'url' => route('freezer.pickup', $item),
                                    'id' => $item->id,
                                    'label' => $item->label(),
                                    'customer' => $item->customer->name,
                                    'days' => $days($stored),
                                ]) }}'
                                    class="inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-xl bg-brand px-4 font-bold text-white transition hover:bg-brand-deep active:scale-[.97]">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10" /></svg>
                                    استلام
                                </button>

                                <form method="POST" action="{{ route('freezer.destroy', $item) }}" data-submit-lock
                                    data-confirm-title="حذف {{ $item->label() }}؟"
                                    data-confirm="سيُحذف الغرض من قائمة الفريزر. استخدم هذا فقط إن سُجّل بالخطأ؛ عند تسليمه اضغط «استلام»."
                                    data-confirm-action="حذف">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="حذف {{ $item->label() }} باسم {{ $item->customer->name }}"
                                        class="flex size-11 items-center justify-center rounded-xl border border-rule-strong text-ink-muted transition hover:border-debit/40 hover:bg-debit-soft hover:text-debit">
                                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" /></svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- The last week's pickups, for when a customer asks --}}
        @if ($recent->isNotEmpty())
            <section aria-labelledby="freezer-recent-title" class="animate-rise max-lg:pb-20">
                <h2 id="freezer-recent-title" class="mb-3 text-lg font-extrabold">استُلمت مؤخراً <span class="text-sm font-medium text-ink-muted">آخر 7 أيام</span></h2>

                <ul class="divide-y divide-rule overflow-hidden rounded-2xl border border-rule bg-white">
                    @foreach ($recent as $item)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 sm:px-5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold">{{ $item->label() }} <span class="font-medium text-ink-muted">· {{ $item->customer->name }}</span></p>
                                <p class="text-xs text-ink-muted">
                                    سلّمه {{ $item->pickedUpBy?->name }} {{ $item->picked_up_at->translatedFormat('l j F، H:i') }} · بقي {{ $stayed($item->daysStored()) }}
                                </p>
                            </div>
                            @if ($item->transaction)
                                <span class="font-extrabold tabular-nums text-debit">{{ $item->transaction->amount }} ₪</span>
                            @else
                                <span class="rounded-md border border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">بدون مقابل</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Phone: the add button stays in reach --}}
        <button type="button" data-open-freezer
            class="fixed bottom-22 end-4 z-30 inline-flex h-14 items-center gap-2 rounded-2xl bg-brand px-5 text-lg font-bold text-white shadow-xl shadow-brand/30 transition active:scale-[.97] lg:hidden">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            وضع غرض
        </button>

        @include('freezer.partials.store-dialog')
        @include('freezer.partials.pickup-dialog')
    </div>
@endsection
