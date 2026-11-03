@extends('layouts.app')

@section('title', 'الرئيسية')

@php
    $greeting = now()->hour < 12 ? 'صباح الخير' : 'مساء الخير';
    $firstName = Str::before(auth()->user()->name, ' ');
@endphp

@section('content')
    <div class="stagger space-y-8">
        <header>
            <p class="text-sm font-medium text-ink-muted">{{ now()->translatedFormat('l، j F Y') }}</p>
            <h1 class="mt-1 text-3xl font-extrabold lg:text-4xl">{{ $greeting }}، {{ $firstName }}</h1>
        </header>

        <section class="ledger-paper relative overflow-hidden rounded-2xl border border-rule py-8 ps-14 pe-6 lg:py-10 lg:ps-16 lg:pe-10">
            <x-logo class="size-12" />
            <h2 class="mt-5 text-2xl font-extrabold">النظام جاهز للعمل</h2>
            <div class="mt-3 h-[7px] w-16 border-y-2 border-debit"></div>
            <p class="mt-4 max-w-xl leading-relaxed text-ink-muted">
                ابدأ بإضافة الزبائن، ثم سجّل ما عليهم وما دفعوه. ستظهر هنا أحدث الحركات وأعلى الأرصدة فور تسجيلها.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-extrabold">الأقسام</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-rule bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-brand text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4" /><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6" /><path d="M16 4a4 4 0 0 1 0 8M22 20c0-3-2-5.2-5-5.8" /></svg>
                        </span>
                        <span class="rounded-md border border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">قريباً</span>
                    </div>
                    <h3 class="mt-4 text-lg font-bold">الزبائن</h3>
                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">بيانات كل زبون ورصيده وكشف حسابه.</p>
                </div>

                <div class="rounded-2xl border border-rule bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-debit text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19h16" /><path d="M7 15V9M12 15V5M17 15v-3" /></svg>
                        </span>
                        <span class="rounded-md border border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">قريباً</span>
                    </div>
                    <h3 class="mt-4 text-lg font-bold">المديونون</h3>
                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">الزبائن المدينون مرتبين من الأعلى رصيداً.</p>
                </div>

                <div class="rounded-2xl border border-rule bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-credit text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3l4 4-4 4" /><path d="M3 7h18" /><path d="M7 21l-4-4 4-4" /><path d="M21 17H3" /></svg>
                        </span>
                        <span class="rounded-md border border-rule-strong px-2 py-0.5 text-xs font-bold text-ink-muted">قريباً</span>
                    </div>
                    <h3 class="mt-4 text-lg font-bold">الحوالات</h3>
                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">الحوالات التي لم يُتأكد من وصولها بعد.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
