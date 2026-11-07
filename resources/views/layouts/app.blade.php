@php
    $user = auth()->user();
    $initial = mb_substr($user->name, 0, 1);
    $role = $user->is_owner ? 'المالك' : 'مستخدم';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-dvh bg-sheet text-ink antialiased">
        <x-flash />
        <x-confirm-dialog />

        <div class="lg:flex">
            {{-- Sidebar (laptop) --}}
            <aside class="sticky top-0 hidden h-dvh w-72 shrink-0 flex-col border-e border-rule bg-paper/50 px-4 py-6 lg:flex">
                <a href="{{ route('day.today') }}" class="flex items-center gap-2.5 px-3">
                    <x-logo class="size-9" />
                    <span class="text-xl font-extrabold">{{ config('app.name') }}</span>
                </a>

                <nav aria-label="التنقل الرئيسي" class="mt-10 flex flex-col gap-1">
                    @include('layouts.partials.nav', ['layout' => 'side'])
                </nav>

                <div class="mt-auto flex items-center gap-3 border-t border-rule px-2 pt-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-base font-bold text-white">{{ $initial }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold">{{ $user->name }}</p>
                        <p class="text-xs text-ink-muted">{{ $role }}</p>
                    </div>
                    <a href="{{ route('passkeys.index') }}" aria-label="الدخول بالبصمة" title="الدخول بالبصمة"
                        class="flex size-11 items-center justify-center rounded-xl text-ink-muted transition hover:bg-paper hover:text-brand">
                        @include('passkeys.partials.icon', ['class' => 'size-5'])
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="تسجيل الخروج" title="تسجيل الخروج" class="flex size-11 items-center justify-center rounded-xl text-ink-muted transition hover:bg-paper hover:text-debit">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3" /><path d="M10 17l-5-5 5-5" /><path d="M5 12h11" /></svg>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                {{-- Top bar (phone) --}}
                <header class="sticky top-0 z-30 flex h-15 items-center justify-between border-b border-rule bg-sheet px-4 lg:hidden">
                    <a href="{{ route('day.today') }}" class="flex items-center gap-2">
                        <x-logo class="size-7" />
                        <span class="text-lg font-extrabold">{{ config('app.name') }}</span>
                    </a>

                    <details class="group relative">
                        <summary class="flex h-11 list-none items-center gap-2 rounded-xl ps-2 pe-1 [&::-webkit-details-marker]:hidden">
                            <span class="text-sm font-medium text-ink-muted">{{ $user->name }}</span>
                            <span class="flex size-9 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">{{ $initial }}</span>
                        </summary>

                        <div class="absolute end-0 top-full mt-2 w-60 animate-drop rounded-2xl border border-rule bg-white p-2 shadow-xl shadow-ink/10">
                            <div class="px-3 py-2">
                                <p class="font-bold">{{ $user->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $role }}</p>
                            </div>
                            <a href="{{ route('passkeys.index') }}" class="flex h-11 items-center gap-2 rounded-xl px-3 font-bold transition hover:bg-paper">
                                @include('passkeys.partials.icon', ['class' => 'size-5'])
                                الدخول بالبصمة
                            </a>

                            @can('manage-users')
                                <a href="{{ route('users.index') }}" class="flex h-11 items-center gap-2 rounded-xl px-3 font-bold transition hover:bg-paper">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="4" /><path d="M3 20c0-3.5 3-6 7-6 1.4 0 2.7.3 3.8.9" /><path d="M18 14v6M15 17h6" /></svg>
                                    المستخدمون
                                </a>
                            @endcan

                            @can('manage-prices')
                                <a href="{{ route('prices.index') }}" class="flex h-11 items-center gap-2 rounded-xl px-3 font-bold transition hover:bg-paper">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8z" /><circle cx="7.5" cy="8.5" r="1.5" /></svg>
                                    الأصناف والأسعار
                                </a>
                            @endcan

                            @can('manage-payment-accounts')
                                <a href="{{ route('payment-accounts.index') }}" class="flex h-11 items-center gap-2 rounded-xl px-3 font-bold transition hover:bg-paper">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h4" /></svg>
                                    حسابات التحويل
                                </a>
                            @endcan

                            @can('move-old-debts')
                                <a href="{{ route('old-debts.index') }}" class="flex h-11 items-center gap-2 rounded-xl px-3 font-bold transition hover:bg-paper">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H19v16H5.5A1.5 1.5 0 0 0 4 20.5z" /><path d="M4 20.5A1.5 1.5 0 0 0 5.5 22H19v-3" /><path d="M8 7h7M8 11h5" /></svg>
                                    نقل الديون السابقة
                                </a>
                            @endcan

                            <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-rule pt-2">
                                @csrf
                                <button type="submit" class="flex h-11 w-full items-center gap-2 rounded-xl px-3 font-bold text-debit transition hover:bg-debit-soft">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3" /><path d="M10 17l-5-5 5-5" /><path d="M5 12h11" /></svg>
                                    تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </details>
                </header>

                <main class="mx-auto w-full max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-10 lg:pb-12 lg:pt-8 xl:px-14">
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- Bottom navigation (phone) --}}
        <nav aria-label="التنقل الرئيسي" data-bottom-nav class="fixed inset-x-0 bottom-0 z-30 grid h-18 grid-cols-5 border-t border-rule bg-sheet pb-[env(safe-area-inset-bottom)] lg:hidden">
            @include('layouts.partials.nav', ['layout' => 'bottom'])
        </nav>
    </body>
</html>
