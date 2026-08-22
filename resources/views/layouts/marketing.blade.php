<!DOCTYPE html>
{{-- scroll-smooth lives here, not in app.css: that stylesheet is shared with
     the EMR, and easing the scroll of a long patient table would be worse,
     not better. The reduced-motion guard in app.css already resets it. --}}
<html lang="en" class="scroll-smooth">
<head>
    @include('marketing.partials.head')
</head>
<body class="bg-page font-sans text-ink-body antialiased">

    {{-- Skip link: the app has no sr-only precedent, so this is the first one. --}}
    <a href="#main"
        class="sr-only focus:not-sr-only focus:absolute focus:z-[110] focus:top-4 focus:left-4
            focus:bg-ink focus:text-white focus:rounded-full focus:px-5 focus:py-2.5 focus:text-sm focus:font-medium">
        Skip to content
    </a>

    {{-- Pages with a dark hero opt in via @section('nav_overlay'); everything
         else gets the solid bar from the first pixel. --}}
    <x-marketing-nav :overlay="View::hasSection('nav_overlay')" />

    <main id="main">
        @yield('content')
    </main>

    <x-marketing-footer />

    {{-- Reuse the app's single toast mechanism — do not add a second one. --}}
    <x-toast />

    @stack('scripts')
</body>
</html>
