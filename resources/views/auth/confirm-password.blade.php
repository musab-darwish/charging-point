@extends('layouts.app')

@section('title', 'تأكيد كلمة المرور')

@section('content')
    <div class="flex min-h-[60dvh] items-center justify-center">
        <div class="stagger w-full max-w-md rounded-3xl border border-rule bg-white p-6 sm:p-8">
            <span class="flex size-14 items-center justify-center rounded-full bg-brand-soft text-brand">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V8a4 4 0 0 1 8 0v3" /></svg>
            </span>

            <div>
                <h1 class="mt-5 text-2xl font-extrabold">تأكيد كلمة المرور</h1>
                <p class="mt-2 leading-relaxed text-ink-muted">لحماية حسابك، أكّد كلمة المرور قبل إدارة الدخول بالبصمة. لن يُطلب منك ذلك مرة أخرى خلال ثلاث ساعات.</p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" data-submit-lock class="mt-6 space-y-5">
                @csrf
                <x-input name="password" type="password" label="كلمة المرور" required autofocus autocomplete="current-password" />
                <x-button class="w-full" loading="جارٍ التأكيد…">تأكيد</x-button>
            </form>
        </div>
    </div>
@endsection
