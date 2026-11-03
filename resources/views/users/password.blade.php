@extends('layouts.app')

@section('title', 'تغيير كلمة المرور')

@section('content')
    <div class="stagger max-w-xl">
        <a href="{{ route('users.index') }}" class="inline-flex h-11 items-center gap-1.5 text-sm font-bold text-ink-muted transition hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
            المستخدمون
        </a>

        <header class="mt-2">
            <h1 class="text-3xl font-extrabold">تغيير كلمة المرور</h1>
            <p class="mt-2 text-ink-muted">
                @if ($user->is(auth()->user()))
                    كلمة مرور حسابك. ستبقى مسجّلاً على هذا الجهاز، ويُسجَّل خروجك من الأجهزة الأخرى.
                @else
                    كلمة مرور <span class="font-bold text-ink">{{ $user->name }}</span>. سيُسجَّل خروجه من كل الأجهزة، ويدخل بكلمة المرور الجديدة.
                @endif
            </p>
        </header>

        <form method="POST" action="{{ route('users.password.update', $user) }}" data-submit-lock class="mt-8 space-y-5 rounded-2xl border border-rule bg-white p-5 sm:p-8">
            @csrf
            @method('PUT')

            <x-input name="password" type="password" label="كلمة المرور الجديدة" required autofocus autocomplete="new-password" hint="8 أحرف على الأقل." />

            <x-input name="password_confirmation" type="password" label="تأكيد كلمة المرور" required autocomplete="new-password" />

            <div class="flex flex-col-reverse gap-3 border-t border-rule pt-5 sm:flex-row">
                <x-button class="sm:min-w-40" loading="جارٍ الحفظ…">حفظ كلمة المرور</x-button>
                <a href="{{ route('users.index') }}" class="inline-flex h-13 items-center justify-center rounded-xl px-5 font-bold text-ink transition hover:bg-paper">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
