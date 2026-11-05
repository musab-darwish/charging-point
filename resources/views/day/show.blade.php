@extends('layouts.app')

@section('title', $sheet->day->isToday() ? 'شغل اليوم' : 'شغل '.$sheet->day->translatedFormat('j F'))

@section('content')
    <div class="space-y-6">
        @include('day.partials.header')

        @if ($errors->any())
            <div role="alert" class="flex animate-drop items-start gap-3 rounded-2xl bg-debit-soft px-5 py-4 text-debit">
                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7.5v5.5M12 16.5v.01" /></svg>
                <div>
                    <p class="font-bold">لم يُسجَّل شيء. صحّح ما يلي:</p>
                    <ul class="mt-1 list-disc space-y-0.5 ps-5 text-sm font-medium">
                        @foreach (array_unique($errors->all()) as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @include('day.partials.summary')

        <div class="grid items-start gap-6 lg:grid-cols-[25rem_minmax(0,1fr)] xl:grid-cols-[27rem_minmax(0,1fr)]">
            @include('day.partials.entry-form')

            <section aria-labelledby="day-customers-title" class="min-w-0 max-lg:pb-20">
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 id="day-customers-title" class="text-lg font-extrabold">زبائن {{ $sheet->day->isToday() ? 'اليوم' : 'هذا اليوم' }}</h2>
                    @if ($sheet->rows->isNotEmpty())
                        <span class="text-sm text-ink-muted">الأحدث أولاً</span>
                    @endif
                </div>

                @forelse ($sheet->rows as $row)
                    @include('day.partials.customer-card')
                @empty
                    <div class="ledger-paper rounded-2xl border border-rule py-14 ps-14 pe-6 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-white text-ink-muted">
                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h11l3 3v15H5z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg>
                        </span>
                        <p class="mt-4 text-lg font-bold">لم يُسجَّل أي شغل في هذا اليوم بعد</p>
                        <p class="mt-1 text-ink-muted">اختر الزبون واضغط على الأصناف لتسجيل شغله.</p>
                        <button type="button" data-open-entry class="mt-5 inline-flex h-12 items-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition hover:bg-brand-deep active:scale-[.97] lg:hidden">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            تسجيل شغل
                        </button>
                    </div>
                @endforelse
            </section>
        </div>

        {{-- Phone: the recording form opens as a full-screen sheet --}}
        <button type="button" data-open-entry
            class="fixed bottom-22 end-4 z-30 inline-flex h-14 items-center gap-2 rounded-2xl bg-brand px-5 text-lg font-bold text-white shadow-xl shadow-brand/30 transition active:scale-[.97] lg:hidden">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            تسجيل شغل
        </button>

        @include('day.partials.payment-dialog')
    </div>
@endsection
