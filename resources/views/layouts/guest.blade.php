<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Same partial the marketing layouts use. The font preloads in particular
         must not be maintained in three places: font-display is `optional`, so a
         layout that forgets one silently renders in the fallback for that visit. --}}
    @include('marketing.partials.head')
</head>
<body class="bg-warm font-sans text-ink-body antialiased">

    <a href="#auth-card"
        class="sr-only focus:not-sr-only focus:absolute focus:z-[110] focus:top-4 focus:left-4
            focus:bg-ink focus:text-white focus:rounded-full focus:px-5 focus:py-2.5 focus:text-sm focus:font-medium">
        Skip to content
    </a>

    {{--
        Shared auth shell for every Tier-2 page: login, password reset, email
        verification, and later invite acceptance.

        Minimal top bar rather than the marketing nav. Someone signing in has a
        destination already, and the full menu is an invitation to wander off it
        — but a page collecting a password with no identity on it and no way back
        reads as a phishing form, so the brand and one exit stay.
    --}}
    <header class="border-b border-line bg-page">
        <div class="max-w-5xl mx-auto px-6">
            <div class="h-16 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-card">
                    <img src="{{ asset('images/africhart-logo.svg') }}" alt="" class="w-8 h-8">
                    <span class="text-lg font-medium tracking-tight text-ink">AfriChart</span>
                </a>

                <a href="{{ route('home') }}"
                    class="group inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink transition-colors rounded">
                    <x-phosphor-arrow-left class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" aria-hidden="true" />
                    Back to site
                </a>
            </div>
        </div>
    </header>

    {{-- min-h calc leaves room for the bar so the card stays optically centred
         rather than sitting a bar's height low. --}}
    <main class="min-h-[calc(100svh-4rem)] flex items-center justify-center p-6">
        <div id="auth-card" class="w-full max-w-104">

            @yield('content')

            <p class="text-center text-xs text-muted mt-6">AfriChart EMR — Electronic Medical Records</p>
        </div>
    </main>

    <x-toast />

    @stack('scripts')
</body>
</html>
