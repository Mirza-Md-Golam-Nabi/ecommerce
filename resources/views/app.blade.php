<!DOCTYPE html>
<html lang="bn">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="{{ \App\Support\Theme::current()->colors['primary'] ?? '' }}">

        <title inertia>{{ app(\App\Settings\StoreSettings::class)->storeName() }}</title>

        {{-- Theme colors are rendered server-side so the first paint already uses the admin-selected theme. --}}
        <style>:root{ {{ \App\Support\Theme::current()->cssVariables() }} }</style>

        @fonts
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="bg-surface font-sans text-ink antialiased">
        @inertia
    </body>
</html>
