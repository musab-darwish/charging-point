@extends('layouts.app')

@section('title', 'الزبائن')

@section('content')
    <div class="stagger space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">الزبائن</h1>
                <p class="mt-2 text-ink-muted">
                    @if ($total)
                        {{ trans_choice('{1} زبون واحد مسجّل.|{2} زبونان مسجّلان.|[3,10] :count زبائن مسجّلين.|[11,*] :count زبوناً مسجّلاً.', $total) }}
                    @else
                        لم يُضَف أي زبون بعد.
                    @endif
                </p>
            </div>

            <a href="{{ route('customers.create') }}" class="inline-flex h-13 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                إضافة زبون
            </a>
        </header>

        <form method="GET" action="{{ route('customers.index') }}" role="search" data-live-search>
            <label for="customer-search" class="sr-only">البحث عن زبون</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-ink-muted">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" /></svg>
                </span>
                <input id="customer-search" type="search" name="q" value="{{ $search }}" autocomplete="off"
                    placeholder="ابحث بالاسم أو رقم الجوال أو الملاحظة"
                    class="block h-14 w-full rounded-2xl border border-rule-strong bg-white ps-12 pe-12 text-lg transition placeholder:text-ink-muted/60 hover:border-ink-muted focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/15">
                <span data-live-search-spinner class="pointer-events-none absolute inset-y-0 end-4 hidden items-center text-ink-muted">
                    <svg class="size-5 animate-spin-fast" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                </span>
            </div>
        </form>

        <div data-live-search-results aria-live="polite">
            @include('customers.partials.list')
        </div>
    </div>
@endsection
