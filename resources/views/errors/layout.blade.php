@extends('layouts.auth')

@php
    // "رجوع" only makes sense when there is a page to go back to.
    $previous = url()->previous();
    $canGoBack = $previous !== url()->current() && $previous !== url('/');
@endphp

@section('content')
    {{-- A card in the middle of the ledger page; on a phone it keeps clear of the red margin line --}}
    <main class="ledger-paper flex min-h-dvh items-center justify-center py-10 ps-12 pe-4 sm:px-6">
        <div class="stagger w-full max-w-xl rounded-3xl border border-rule bg-white px-6 py-10 text-center shadow-xl shadow-ink/5 sm:px-12 sm:py-14">
            <x-logo animated class="mx-auto size-16 sm:size-20" />

            <p class="mt-8 text-sm font-bold text-ink-muted">خطأ</p>
            <p class="text-7xl font-extrabold leading-none tabular-nums text-debit sm:text-8xl">@yield('code')</p>
            <div class="mx-auto mt-5 h-[9px] w-24 border-y-2 border-debit"></div>

            <h1 class="mt-7 text-2xl font-extrabold leading-snug sm:text-3xl">@yield('heading')</h1>
            <p class="mx-auto mt-3 max-w-sm text-lg leading-relaxed text-ink-muted">@yield('message')</p>

            <div class="mt-9 flex flex-col-reverse justify-center gap-3 sm:flex-row">
                @if ($canGoBack)
                    <a href="{{ $previous }}" class="inline-flex h-13 items-center justify-center gap-2 rounded-xl border-2 border-rule-strong px-6 font-bold transition duration-150 hover:bg-paper active:scale-[.97]">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
                        رجوع
                    </a>
                @endif
                <a href="{{ url('/') }}" class="inline-flex h-13 items-center justify-center gap-2 rounded-xl bg-brand px-6 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h11l3 3v15H5z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg>
                    العودة إلى الرئيسية
                </a>
            </div>
        </div>
    </main>
@endsection
