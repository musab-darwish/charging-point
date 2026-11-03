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

        <div class="lg:flex">
            {{-- Sidebar (laptop) --}}
            <aside class="sticky top-0 hidden h-dvh w-72 shrink-0 flex-col border-e border-rule bg-paper/50 px-4 py-6 lg:flex">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-3">
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
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
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
                            @can('manage-users')
                                <a href="{{ route('users.index') }}" class="flex h-11 items-center gap-2 rounded-xl px-3 font-bold transition hover:bg-paper">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="4" /><path d="M3 20c0-3.5 3-6 7-6 1.4 0 2.7.3 3.8.9" /><path d="M18 14v6M15 17h6" /></svg>
                                    المستخدمون
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

                <main class="mx-auto w-full max-w-5xl px-4 pb-28 pt-6 sm:px-6 lg:px-12 lg:pb-14 lg:pt-12">
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- Bottom navigation (phone) --}}
        <nav aria-label="التنقل الرئيسي" class="fixed inset-x-0 bottom-0 z-30 grid h-18 grid-cols-4 border-t border-rule bg-sheet pb-[env(safe-area-inset-bottom)] lg:hidden">
            @include('layouts.partials.nav', ['layout' => 'bottom'])
        </nav>
    </body>
</html>
