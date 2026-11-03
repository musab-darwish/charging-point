@extends('layouts.auth')

@section('title', 'تسجيل الدخول')

@section('content')
    <div class="grid min-h-dvh lg:grid-cols-[1.15fr_1fr]">

        {{-- Brand panel: ledger paper, the mark and what the system does --}}
        <aside class="ledger-paper relative overflow-hidden border-b border-rule ps-14 pe-6 pb-10 pt-12 sm:px-14 lg:flex lg:min-h-dvh lg:flex-col lg:border-b-0 lg:border-e lg:px-20 lg:py-16">
            <div class="hidden animate-fade items-center gap-2.5 lg:flex">
                <x-logo class="size-8" />
                <span class="text-lg font-extrabold">{{ config('app.name') }}</span>
            </div>

            <div class="stagger lg:my-auto lg:max-w-xl">
                <x-logo animated class="size-16 sm:size-20 lg:size-28" />

                <h1 class="mt-6 text-3xl font-extrabold leading-tight sm:text-4xl lg:mt-8 lg:text-5xl lg:leading-[1.2]">
                    نظامك المتكامل لإدارة نقطة الشحن
                </h1>

                <div class="mt-4 h-[7px] w-24 border-y-2 border-debit"></div>

                <p class="mt-5 max-w-md text-base leading-relaxed text-ink-muted sm:text-lg">
                    سجّل ما على كل زبون وما دفعه، وتابع الحوالات حتى تتأكد من وصولها، وأرسل له كشف حسابه برسالة واحدة.
                </p>

                <ul class="mt-10 hidden space-y-4 lg:block">
                    <li class="flex items-center gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-ink text-sheet">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z" /><path d="M4 10h16M9 10v9" /></svg>
                        </span>
                        <span class="text-lg font-medium">حسابات الزبائن والديون بدقة</span>
                    </li>
                    <li class="flex items-center gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-credit text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3l4 4-4 4" /><path d="M3 7h18" /><path d="M7 21l-4-4 4-4" /><path d="M21 17H3" /></svg>
                        </span>
                        <span class="text-lg font-medium">متابعة الحوالات وتأكيد وصولها</span>
                    </li>
                    <li class="flex items-center gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-debit text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" /></svg>
                        </span>
                        <span class="text-lg font-medium">كشف الحساب جاهز للإرسال عبر واتساب</span>
                    </li>
                </ul>
            </div>

            {{-- A ledger slip, purely decorative --}}
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-6 end-14 hidden w-64 rotate-[-4deg] animate-rise rounded-2xl border border-rule bg-sheet p-5 shadow-xl shadow-ink/10 [animation-delay:500ms] 2xl:block">
                <div class="h-2.5 w-24 rounded-full bg-rule"></div>
                <div class="mt-5 space-y-3">
                    <div class="flex justify-between"><span class="h-2 w-28 rounded-full bg-rule"></span><span class="h-2 w-10 rounded-full bg-debit/60"></span></div>
                    <div class="flex justify-between"><span class="h-2 w-20 rounded-full bg-rule"></span><span class="h-2 w-12 rounded-full bg-credit/60"></span></div>
                    <div class="flex justify-between"><span class="h-2 w-24 rounded-full bg-rule"></span><span class="h-2 w-9 rounded-full bg-debit/60"></span></div>
                </div>
                <div class="mt-6 border-t border-rule pt-4">
                    <p class="text-xs font-bold text-ink-muted">الرصيد</p>
                    <p class="text-3xl font-extrabold tabular-nums text-debit">47.50 <span class="text-lg">₪</span></p>
                    <div class="mt-1 h-[7px] w-20 border-y-2 border-debit"></div>
                </div>
                <div class="h-10"></div>
            </div>
        </aside>

        {{-- Login form --}}
        <main class="flex justify-center px-6 py-10 sm:px-12 lg:items-center lg:py-16">
            <div class="stagger w-full max-w-sm">
                <div>
                    <h2 class="text-2xl font-extrabold sm:text-3xl">تسجيل الدخول</h2>
                    <p class="mt-2 text-ink-muted">أدخل بيانات حسابك للمتابعة.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" data-submit-lock class="mt-8 space-y-5">
                    @csrf

                    <x-input name="username" label="اسم المستخدم" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false" />

                    <x-input name="password" type="password" label="كلمة المرور" required autocomplete="current-password" />

                    <label class="flex h-11 w-fit items-center gap-3">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-5 rounded accent-ink">
                        <span>تذكّرني على هذا الجهاز</span>
                    </label>

                    <x-button class="w-full text-lg" loading="جارٍ تسجيل الدخول…">تسجيل الدخول</x-button>
                </form>

                <p class="mt-10 border-t border-rule pt-5 text-sm leading-relaxed text-ink-muted">
                    يُنشئ المالك حسابات المستخدمين. إذا لم يكن لديك حساب، تواصل معه.
                </p>
            </div>
        </main>
    </div>
@endsection
