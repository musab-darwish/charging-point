<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#F5F7F2">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">

{{-- Installable on the phone's home screen: name, icon and colors live in the manifest --}}
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

{{-- Hide the native cursor before the page paints (mouse only), so it doesn't
     flash before the custom one loads. If the JS never loads, bring it back. --}}
<script>
    if (matchMedia('(hover: hover) and (pointer: fine)').matches) {
        document.documentElement.classList.add('has-custom-cursor');
        setTimeout(() => window.__customCursorReady || document.documentElement.classList.remove('has-custom-cursor'), 2000);
    }
</script>

@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
