@extends('layouts.app')

@section('title', 'إضافة مستخدم')

@section('content')
    <div class="stagger">
        <a href="{{ route('users.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            المستخدمون
        </a>

        <header>
            <h1 class="text-3xl font-extrabold">إضافة مستخدم</h1>
            <p class="mt-2 text-ink-muted">أخ أو عامل يدخل إلى النظام ويسجّل الشغل.</p>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <form method="POST" action="{{ route('users.store') }}" data-submit-lock class="rounded-2xl border border-rule bg-white p-5 sm:p-7">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <x-input name="name" label="الاسم" required autofocus autocomplete="off" hint="يظهر على الحركات التي يسجّلها." />

                    <x-input name="username" label="اسم المستخدم" required dir="ltr" autocomplete="off" autocapitalize="none" spellcheck="false"
                        hint="أحرف إنجليزية صغيرة وأرقام، بدون مسافات." />

                    <x-input name="password" type="password" label="كلمة المرور" required autocomplete="new-password" hint="8 أحرف على الأقل." />

                    <x-input name="password_confirmation" type="password" label="تأكيد كلمة المرور" required autocomplete="new-password" />
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                    <x-button class="sm:min-w-44" loading="جارٍ الحفظ…">إضافة المستخدم</x-button>
                    <a href="{{ route('users.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
                </div>
            </form>

            @include('users.partials.permissions')
        </div>
    </div>
@endsection
