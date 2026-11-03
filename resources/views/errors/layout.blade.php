@extends('layouts.auth')

@section('content')
    <main class="ledger-paper flex min-h-dvh items-center justify-center py-10 ps-14 pe-6">
        <div class="stagger max-w-md">
            <x-logo animated class="size-16" />

            <p class="mt-8 text-sm font-bold tabular-nums text-debit">خطأ @yield('code')</p>
            <h1 class="mt-2 text-3xl font-extrabold leading-snug">@yield('heading')</h1>
            <div class="mt-4 h-[7px] w-16 border-y-2 border-debit"></div>
            <p class="mt-5 leading-relaxed text-ink-muted">@yield('message')</p>

            <div>
                <a href="{{ url('/') }}" class="mt-8 inline-flex h-13 items-center gap-2 rounded-xl bg-brand px-5 font-bold text-white transition duration-150 hover:bg-brand-deep active:scale-[.97]">
                    العودة إلى الرئيسية
                </a>
            </div>
        </div>
    </main>
@endsection
