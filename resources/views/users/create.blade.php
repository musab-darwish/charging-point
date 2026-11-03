@extends('layouts.app')

@section('title', 'إضافة مستخدم')

@section('content')
    <div class="stagger max-w-xl">
        <a href="{{ route('users.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            المستخدمون
        </a>

        <header class="mt-2">
            <h1 class="text-3xl font-extrabold">إضافة مستخدم</h1>
            <p class="mt-2 text-ink-muted">أخ أو عامل يستطيع تسجيل الشغل والدفعات. لا يستطيع إدارة المستخدمين أو الأسعار.</p>
        </header>

        <form method="POST" action="{{ route('users.store') }}" data-submit-lock class="mt-8 space-y-5 rounded-2xl border border-rule bg-white p-5 sm:p-8">
            @csrf

            <x-input name="name" label="الاسم" required autofocus autocomplete="off" hint="كما سيظهر في النظام وعلى الحركات التي يسجّلها." />

            <x-input name="username" label="اسم المستخدم" required dir="ltr" autocomplete="off" autocapitalize="none" spellcheck="false"
                hint="يُستخدم لتسجيل الدخول: أحرف إنجليزية صغيرة وأرقام، بدون مسافات." />

            <x-input name="password" type="password" label="كلمة المرور" required autocomplete="new-password" hint="8 أحرف على الأقل." />

            <x-input name="password_confirmation" type="password" label="تأكيد كلمة المرور" required autocomplete="new-password" />

            <div class="flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                <x-button class="sm:min-w-40" loading="جارٍ الحفظ…">إضافة المستخدم</x-button>
                <a href="{{ route('users.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
