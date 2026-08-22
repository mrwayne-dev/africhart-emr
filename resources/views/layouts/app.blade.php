<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AfriChart EMR')</title>
    {{-- Preload the weights actually used, so font-display: optional has the
         file in hand before its window closes and text never reflows. --}}
    <link rel="preload" href="{{ asset('fonts/general-sans-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/general-sans-500.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/general-sans-600.woff2') }}" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page font-sans text-ink-body antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen md:flex">

        {{-- Sidebar (off-canvas drawer on mobile, static on desktop) --}}
        <x-sidebar />

        {{-- Main column --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <x-topbar />

            {{-- Page content --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast notifications (driven by session flash + JS events) --}}
    <x-toast />

    @stack('modals')
    @stack('scripts')
</body>
</html>
