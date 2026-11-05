@extends('layouts.app')

@section('title', 'إضافة حساب تحويل')

@section('content')
    <div class="stagger">
        <a href="{{ route('payment-accounts.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            حسابات التحويل
        </a>

        <header>
            <h1 class="text-3xl font-extrabold">إضافة حساب تحويل</h1>
            <p class="mt-2 text-ink-muted">حساب بنكي أو محفظة يحوّل عليها الزبائن.</p>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <form method="POST" action="{{ route('payment-accounts.store') }}" data-submit-lock class="rounded-2xl border border-rule bg-white p-5 sm:p-7">
                @csrf

                @include('payment-accounts.partials.form', ['account' => null])

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                    <x-button class="sm:min-w-44" loading="جارٍ الحفظ…">إضافة الحساب</x-button>
                    <a href="{{ route('payment-accounts.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
                </div>
            </form>

            @include('payment-accounts.partials.help')
        </div>
    </div>
@endsection
