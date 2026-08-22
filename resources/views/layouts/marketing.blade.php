<!DOCTYPE html>
{{-- scroll-smooth lives here, not in app.css: that stylesheet is shared with
     the EMR, and easing the scroll of a long patient table would be worse,
     not better. The reduced-motion guard in app.css already resets it. --}}
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AfriChart EMR')</title>

    {{--
        The app layouts carry no SEO metadata at all — reasonable for a login-gated
        EMR, useless for a marketing site. Each page sets its own description via
        @section('description'); the rest is derived so no page can forget it.
    --}}
    <meta name="description" content="@yield('description', 'Clinic management software for Nigerian private clinics. Patients, queue, consultations, prescriptions and billing in one place.')">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AfriChart">
    <meta property="og:title" content="@yield('og_title', View::getSection('title', 'AfriChart EMR'))">
    <meta property="og:description" content="@yield('description', 'Clinic management software for Nigerian private clinics.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('images/africhart-logo.svg') }}" type="image/svg+xml">

    {{-- Same single bundle as the app: tokens, General Sans and Alpine come free. --}}
    {{-- Preload the weights actually used, so font-display: optional has the
         file in hand before its window closes and text never reflows. --}}
    <link rel="preload" href="{{ asset('fonts/general-sans-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/general-sans-500.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/general-sans-600.woff2') }}" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
