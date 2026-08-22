@props(['overlay' => false])

@php
    /*
     * The four primary links, per the page inventory. One source for desktop
     * and mobile so they cannot drift.
     *
     * There are no dropdowns any more. Product/Company panels were carrying six
     * anchors onto one page and four unrelated destinations; four flat links is
     * the scannable shape on mobile, and About is a trust page reached from the
     * footer rather than a navigation destination. Contact takes its place,
     * because being able to reach a person is a primary need for this audience.
     */
    $links = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Features', 'route' => 'features'],
        ['label' => 'Pricing', 'route' => 'pricing'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

{{--
    Public marketing navigation.

    Brand left, four links centred, actions right: Sign in (quiet text), Book a
    demo (ghost), Get started (solid).

    Get started stays ink rather than brand red. The inventory calls it "the
    accent button", but red currently earns attention on this site precisely
    because it appears about three times a page — spending it on the single most
    repeated element would flatten that everywhere else.
--}}
{{--
    `overlay` lets a page with a dark hero start the bar transparent. It turns
    solid once the hero has scrolled past — a solid white bar clamped on top of
    a full-bleed dark image reads as a hard stripe.

    Detected with an IntersectionObserver on the hero rather than a scroll
    listener: no per-frame work, and it re-measures on resize for free. Pages
    without a dark hero pass overlay=false and the bar is solid from the start.
--}}
<header
    x-data="{
        mobile: false,

        solid: {{ $overlay ? 'false' : 'true' }},

        /*
         * The single source of truth for the bar's colour scheme. The mobile
         * drawer forces the solid scheme, because a white drawer hanging off a
         * transparent bar leaves white text on white.
         */
        get onHero() { return ! this.solid && ! this.mobile },

        init() {
            @if ($overlay)
                const hero = document.querySelector('[data-nav-overlay-anchor]');
                if (! hero || ! ('IntersectionObserver' in window)) { this.solid = true; return; }

                // While the hero still crosses the area below the 4rem bar we are
                // over it, so stay transparent.
                new IntersectionObserver(
                    ([entry]) => { this.solid = ! entry.isIntersecting },
                    { rootMargin: '-64px 0px 0px 0px', threshold: 0 }
                ).observe(hero);
            @endif
        },
    }"
    @keydown.escape.window="mobile = false"
    {{-- -mb-16 in overlay mode pulls the following section up by the bar's own
         height, so the hero starts at the top of the viewport and the sticky bar
         floats OVER it. Without this the bar sits above the hero in normal flow,
         and going transparent just exposes the white page behind it. --}}
    @class(['sticky top-0 z-50', '-mb-16' => $overlay])
>
    <div class="transition-colors duration-500"
        :class="onHero
            ? 'bg-transparent border-b border-transparent nav-overlay'
            : 'bg-page/95 backdrop-blur border-b border-line'">
        <nav class="max-w-7xl mx-auto px-6 sm:px-8" aria-label="Main">
            <div class="flex items-center justify-between h-16">

                {{-- Brand --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 rounded-card">
                    <img src="{{ asset('images/africhart-logo.svg') }}" alt=""
                        class="w-8 h-8 transition-[filter] duration-500" :class="onHero && 'invert'">
                    <span class="text-lg font-medium tracking-tight transition-colors duration-500"
                        :class="onHero ? 'text-white' : 'text-ink'">AfriChart</span>
                </a>

                {{-- The four links (desktop) --}}
                <div class="hidden md:flex items-center gap-1">
                    @foreach ($links as $link)
                        @php $current = request()->routeIs($link['route']); @endphp
                        <a href="{{ route($link['route']) }}"
                            @if ($current) aria-current="page" @endif
                            class="px-3 py-2 text-sm font-medium transition-colors rounded-card"
                            :class="onHero
                                ? '{{ $current ? 'text-white' : 'text-white/70 hover:text-white' }}'
                                : '{{ $current ? 'text-ink' : 'text-muted hover:text-ink' }}'">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>

                {{-- Actions (desktop) --}}
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-3 py-2 text-sm font-medium transition-colors rounded-card"
                            :class="onHero ? 'text-white/70 hover:text-white' : 'text-muted hover:text-ink'">
                            Sign in
                        </a>
                        <a href="{{ route('demo') }}"
                            class="inline-flex items-center border rounded-full px-4 py-2 text-sm font-medium transition-colors"
                            :class="onHero
                                ? 'border-white/30 text-white hover:bg-white/10 hover:border-white/50'
                                : 'border-line text-ink hover:bg-warm hover:border-muted/30'">
                            Book a demo
                        </a>
                        <a href="{{ route('signup') }}"
                            class="group inline-flex items-center gap-1.5 rounded-full px-5 py-2.5 text-sm font-medium transition-colors"
                            :class="onHero ? 'bg-page text-ink hover:bg-warm' : 'bg-ink text-white hover:bg-ink/90'">
                            Get started
                            <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                        </a>
                    @endauth
                </div>

                {{-- Hamburger --}}
                <button type="button" @click="mobile = ! mobile"
                    class="md:hidden transition-colors -mr-1"
                    :class="onHero ? 'text-white/80 hover:text-white' : 'text-muted hover:text-ink'"
                    :aria-expanded="mobile ? 'true' : 'false'"
                    aria-controls="marketing-mobile-nav">
                    <span class="sr-only">Toggle navigation</span>
                    <x-phosphor-list x-show="!mobile" class="w-6 h-6" />
                    <x-phosphor-x x-show="mobile" x-cloak class="w-6 h-6" />
                </button>
            </div>
        </nav>

        {{-- Mobile drawer: the same four links, then the actions. Get started is
             a full-width button at the bottom, per the inventory — it is the one
             action worth a thumb-sized target at the end of the list. --}}
        <div id="marketing-mobile-nav" x-show="mobile" x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="md:hidden border-t border-line bg-page px-6 py-4 max-h-[calc(100svh-4rem)] overflow-y-auto">

            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @click="mobile = false"
                    @if (request()->routeIs($link['route'])) aria-current="page" @endif
                    @class([
                        'block py-3 border-b border-line text-sm font-medium',
                        'text-ink' => request()->routeIs($link['route']),
                        'text-muted' => ! request()->routeIs($link['route']),
                    ])>{{ $link['label'] }}</a>
            @endforeach

            <div class="flex flex-col gap-3 mt-5">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="bg-ink text-white rounded-full px-5 py-3 text-sm font-medium hover:bg-ink/90 transition-colors text-center">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" @click="mobile = false"
                        class="text-sm font-medium text-muted hover:text-ink transition-colors text-center py-1">
                        Sign in
                    </a>
                    <a href="{{ route('demo') }}" @click="mobile = false"
                        class="border border-line text-ink rounded-full px-4 py-3 text-sm font-medium hover:bg-warm transition-colors text-center">
                        Book a demo
                    </a>
                    <a href="{{ route('signup') }}" @click="mobile = false"
                        class="bg-ink text-white rounded-full px-5 py-3 text-sm font-medium hover:bg-ink/90 transition-colors text-center">
                        Get started
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
