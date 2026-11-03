<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-sheet text-ink antialiased">
        <header class="border-b border-rule">
            <div class="mx-auto flex h-14 max-w-3xl items-center justify-between px-4">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-xl font-extrabold">
                    <x-logo class="size-7" />
                    {{ config('app.name') }}
                </a>

                @auth
                    <div class="flex items-center gap-1">
                        <span class="text-sm text-ink-muted">{{ auth()->user()->name }}</span>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex h-11 items-center gap-1.5 rounded-lg px-3 text-sm font-bold hover:bg-paper">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"></path>
                                    <path d="M10 17l-5-5 5-5"></path>
                                    <path d="M5 12h11"></path>
                                </svg>
                                تسجيل الخروج
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-6">
            @yield('content')
        </main>
    </body>
</html>
