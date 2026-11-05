@extends('layouts.app')

@section('title', $sheet->day->isToday() ? 'شغل اليوم' : 'شغل '.$sheet->day->translatedFormat('j F'))

@section('content')
    <div class="space-y-6">
        @include('day.partials.header')

        @include('day.partials.errors')

        @include('day.partials.summary')

        @include('day.partials.entry-form', ['day' => $sheet->day])

        <section aria-labelledby="day-customers-title" class="min-w-0 max-lg:pb-20">
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 id="day-customers-title" class="text-lg font-extrabold">زبائن {{ $sheet->day->isToday() ? 'اليوم' : 'هذا اليوم' }}</h2>
                    @if ($sheet->rows->isNotEmpty())
                        <span class="text-sm text-ink-muted">الأحدث أولاً</span>
                    @endif
                </div>

                @if ($sheet->rows->isNotEmpty())
                    @include('day.partials.filter')

                    <div data-day-cards class="gap-3 lg:columns-2 2xl:columns-3 *:mb-3 *:break-inside-avoid">
                        @foreach ($sheet->rows as $row)
                            @include('day.partials.customer-card')
                        @endforeach
                    </div>

                    <div data-day-no-match hidden class="rounded-2xl border border-dashed border-rule-strong bg-white/60 px-6 py-10 text-center">
                        <p class="text-lg font-bold">لا يوجد زبون يطابق البحث في هذا اليوم</p>
                        <p class="mt-1 text-ink-muted">جرّب اسماً آخر، أو اختر «الكل».</p>
                    </div>
                @else
                    <div class="ledger-paper rounded-2xl border border-rule py-14 ps-14 pe-6 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-ink-muted">
                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h11l3 3v15H5z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg>
                        </span>
                        <p class="mt-4 text-lg font-bold">لم يُسجَّل أي شغل في هذا اليوم بعد</p>
                        <p class="mt-1 text-ink-muted">اختر الزبون واضغط على الأصناف لتسجيل شغله.</p>
                        <button type="button" data-open-entry class="mt-5 inline-flex h-12 items-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition hover:bg-brand-deep active:scale-[.97]">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            تسجيل شغل
                        </button>
                    </div>
                @endif
            </section>

        {{-- Phone: the recording form opens as a full-screen sheet --}}
        <button type="button" data-open-entry
            class="fixed bottom-22 end-4 z-30 inline-flex h-14 items-center gap-2 rounded-2xl bg-brand px-5 text-lg font-bold text-white shadow-xl shadow-brand/30 transition active:scale-[.97] lg:hidden">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            تسجيل شغل
        </button>

        @include('day.partials.payment-dialog', ['day' => $sheet->day])
    </div>
@endsection
