<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-dvh bg-sheet text-ink antialiased">
        <x-flash />

        @yield('content')
    </body>
</html>
