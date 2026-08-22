<!DOCTYPE html>
{{-- scroll-smooth lives here, not in app.css: that stylesheet is shared with
     the EMR, and easing the scroll of a long patient table would be worse,
     not better. The reduced-motion guard in app.css already resets it. --}}
<html lang="en" class="scroll-smooth">
<head>
    @include('marketing.partials.head')
</head>
<body class="bg-page font-sans text-ink-body antialiased">

    <a href="#main"
        class="sr-only focus:not-sr-only focus:absolute focus:z-[110] focus:top-4 focus:left-4
            focus:bg-ink focus:text-white focus:rounded-full focus:px-5 focus:py-2.5 focus:text-sm focus:font-medium">
        Skip to content
    </a>

    {{--
        Conversion layout for Book a demo and Get started.

        Deliberately stripped: no menu, no CTAs, no footer. Someone who has
        reached a form has already chosen — every other link on the page is an
        invitation to leave it. The brand lockup stays, centred, because a page
        collecting a clinic's details with no identity on it reads as a phishing
        form, and it doubles as the only way back out.
    --}}
    <header class="border-b border-line bg-page">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="h-16 flex items-center justify-center">
                <a href="{{ route('home') }}"
                    class="flex items-center gap-2.5 rounded-card"
                    aria-label="AfriChart — back to home">
                    <img src="{{ asset('images/africhart-logo.svg') }}" alt="" class="w-8 h-8">
                    <span class="text-lg font-medium tracking-tight text-ink">AfriChart</span>
                </a>
            </div>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    {{-- Reuse the app's single toast mechanism — do not add a second one. --}}
    <x-toast />

    @stack('scripts')
</body>
</html>
