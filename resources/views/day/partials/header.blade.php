@php
    $day = $sheet->day;
    $previous = $day->subDay();
    $next = $day->addDay();
@endphp

<header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-bold {{ $day->isToday() ? 'text-brand' : 'text-ink-muted' }}">
            {{ $day->isToday() ? 'اليوم' : ($day->isYesterday() ? 'أمس' : 'يوم سابق') }}
        </p>
        <h1 class="mt-0.5 text-3xl font-extrabold">{{ $day->translatedFormat('l، j F Y') }}</h1>
    </div>

    <div class="flex flex-wrap items-center gap-3">
    {{-- The "+" key opens it too; the tooltip says so, so the button shows a single "+" --}}
    <button type="button" data-open-entry title="تسجيل شغل (أو اضغط + على لوحة المفاتيح)" aria-keyshortcuts="+"
        class="hidden h-12 items-center gap-2 rounded-xl bg-brand px-5 text-lg font-bold text-white shadow-lg shadow-brand/20 transition duration-150 hover:bg-brand-deep active:scale-[.97] lg:inline-flex">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
        تسجيل شغل
    </button>

    <nav aria-label="التنقل بين الأيام" class="flex items-center gap-2">
        <a href="{{ \App\Support\WorkDay::url($previous) }}" aria-label="اليوم السابق" title="اليوم السابق"
            class="flex size-11 items-center justify-center rounded-xl border border-rule-strong bg-white transition hover:bg-paper">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
        </a>

        <label class="relative">
            <span class="sr-only">اختيار تاريخ</span>
            <input type="date" data-day-picker data-url="{{ route('day.show', '__DATE__') }}" data-today-url="{{ route('day.today') }}"
                value="{{ $day->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                class="h-11 rounded-xl border border-rule-strong bg-white px-3 font-bold tabular-nums transition hover:bg-paper focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
        </label>

        @if ($day->isToday())
            <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl border border-rule text-rule-strong">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6" /></svg>
            </span>
        @else
            <a href="{{ \App\Support\WorkDay::url($next) }}" aria-label="اليوم التالي" title="اليوم التالي"
                class="flex size-11 items-center justify-center rounded-xl border border-rule-strong bg-white transition hover:bg-paper">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg>
            </a>

            <a href="{{ route('day.today') }}" class="inline-flex h-11 items-center rounded-xl bg-brand px-4 font-bold text-white transition hover:bg-brand-deep">اليوم</a>
        @endif
    </nav>
    </div>
</header>

@unless ($day->isToday())
    <div class="flex items-center gap-3 rounded-2xl border border-dashed border-rule-strong bg-paper/60 px-5 py-3 text-sm">
        <svg class="size-5 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
        <p>أنت تتصفح يوماً سابقاً. ما تسجّله هنا يُحفظ بتاريخ <span class="font-bold">{{ $day->translatedFormat('j F') }}</span>، كما لو كُتب في دفتر ذلك اليوم.</p>
    </div>
@endunless
