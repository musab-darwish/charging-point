<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', config('app.name'))</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-3xl px-4 py-3">
                <a href="{{ url('/') }}" class="text-lg font-bold">{{ config('app.name') }}</a>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-6">
            @yield('content')
        </main>
    </body>
</html>
