<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AfriChart EMR')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-warm font-sans text-ink-body antialiased">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-sm">

            {{-- Brand --}}
            <div class="flex items-center justify-center gap-3 mb-8">
                <img src="{{ asset('images/africhart-logo.svg') }}" alt="AfriChart" class="w-10 h-10">
                <span class="text-2xl font-medium text-ink tracking-tight">AfriChart</span>
            </div>

            @yield('content')

            <p class="text-center text-xs text-muted mt-6">AfriChart EMR — Electronic Medical Records</p>
        </div>
    </div>

    <x-toast />
</body>
</html>
