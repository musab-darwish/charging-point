<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-sheet text-ink antialiased">
        <header class="border-b border-rule">
            <div class="mx-auto flex h-14 max-w-3xl items-center px-4">
                <a href="{{ url('/') }}" class="text-2xl font-extrabold">{{ config('app.name') }}</a>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-6">
            @yield('content')
        </main>
    </body>
</html>
