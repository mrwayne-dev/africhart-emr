{{--
    Shared <head> for both marketing layouts.

    Extracted so layouts/marketing and layouts/focus cannot drift. The font
    preloads in particular must stay identical across the two: font-display is
    `optional`, so a page that forgets to preload silently renders in the
    fallback for that visit and the whole self-hosting exercise is undone.
--}}
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

{{--
    Canonical and og:url echo the CURRENT URL — which is wrong for any page
    whose URL carries a credential in it.

    /invite/{token} and /reset-password/{token} both put a live, single-use
    secret in the path, and this partial was copying it into <link rel=canonical>
    and <meta og:url>. Those are exactly the fields a crawler indexes and a chat
    client reads to build a link preview, so pasting such a link anywhere could
    hand the token to a third party that had no business holding it.

    Pages like that declare @section('private-url') and get noindex instead.
--}}
@hasSection('private-url')
    <meta name="robots" content="noindex, nofollow">
@else
    <link rel="canonical" href="{{ url()->current() }}">
@endif

<meta property="og:type" content="website">
<meta property="og:site_name" content="AfriChart">
<meta property="og:title" content="@yield('og_title', View::getSection('title', 'AfriChart EMR'))">
<meta property="og:description" content="@yield('description', 'Clinic management software for Nigerian private clinics.')">
@hasSection('private-url')
@else
    <meta property="og:url" content="{{ url()->current() }}">
@endif
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="{{ asset('images/africhart-logo.svg') }}" type="image/svg+xml">

{{-- Preload the weights actually used, so font-display: optional has the
     file in hand before its window closes and text never reflows. --}}
<link rel="preload" href="{{ asset('fonts/general-sans-400.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/general-sans-500.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/general-sans-600.woff2') }}" as="font" type="font/woff2" crossorigin>

{{-- Same single bundle as the app: tokens, General Sans and Alpine come free. --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
