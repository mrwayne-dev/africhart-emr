@props(['overlay' => false])

@php
    /*
     * Nav data lives here so the desktop bar and the mobile menu render from one
     * source and cannot drift apart.
     *
     * Four middle items: Features, Company, Pricing, Contact. Company is the
     * only dropdown — its items go to four unrelated destinations, which is what
     * a dropdown is for. The others are single pages and stay plain links.
     */
    $links = [
        ['label' => 'Features', 'route' => 'features'],
        ['label' => 'Company', 'menu' => [
            ['label' => 'About', 'desc' => 'Built in Port Harcourt', 'icon' => 'phosphor-users-three', 'href' => route('about')],
            ['label' => 'Book a demo', 'desc' => 'See it on your workflow', 'icon' => 'phosphor-envelope-simple', 'href' => route('demo')],
            ['label' => 'Privacy', 'desc' => 'How we handle data', 'icon' => 'phosphor-lock-key', 'href' => route('legal.privacy')],
            ['label' => 'Terms', 'desc' => 'Terms of service', 'icon' => 'phosphor-shield-check', 'href' => route('legal.terms')],
        ]],
        ['label' => 'Pricing', 'route' => 'pricing'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

{{--
    Public marketing navigation.

    Brand left, four items centred, two actions right: Book a demo (ghost) and
    Get started (solid).

    No Sign in link. Two actions is what keeps the bar readable, and signing in
    is not what this page is for — a returning user reaches it from the footer,
    from the Get started page, or by going straight to their own clinic's
    subdomain, which is where their login actually lives.

    Get started stays ink rather than brand red. Red earns attention on this site
    precisely because it appears about three times a page — spending it on the
    single most repeated element would flatten that everywhere else.

    Accessibility: hover alone would lock out keyboard and touch users, so the
    Company trigger is a real <button> that opens on focus and Enter/Space,
    closes on Escape, and its panel is reachable by Tab. The close is delayed
    ~150ms so the pointer can cross the gap into the panel without losing it.
--}}
{{--
    `overlay` lets a page with a dark hero start the bar transparent. It turns
    solid once the hero has scrolled past — a solid white bar clamped on top of
    a full-bleed dark image reads as a hard stripe.

    Detected with an IntersectionObserver on the hero rather than a scroll
    listener: no per-frame work, and it re-measures on resize for free.
--}}
<header
    x-data="{
        menu: null,
        closeTimer: null,
        open(name) { clearTimeout(this.closeTimer); this.menu = name },
        scheduleClose() { this.closeTimer = setTimeout(() => (this.menu = null), 150) },
        closeNow() { clearTimeout(this.closeTimer); this.menu = null },

        mobile: false,
        mobileGroup: null,

        solid: {{ $overlay ? 'false' : 'true' }},

        /*
         * The single source of truth for the bar's colour scheme.
         *
         * The bar goes solid whenever a panel is open — otherwise a light
         * dropdown or a light full-screen menu would hang off a transparent bar
         * and leave white text on white. Every binding reads this one getter
         * rather than checking `solid` on its own, which is the bug that
         * produced exactly that once already.
         */
        get onHero() { return ! this.solid && ! this.menu && ! this.mobile },

        /*
         * Lock the page behind the full-screen menu. Without this the body
         * scrolls under the overlay, so closing the menu drops you somewhere
         * you never chose to be.
         */
        toggleMobile() {
            this.mobile = ! this.mobile;
            document.body.classList.toggle('overflow-hidden', this.mobile);
        },

        closeMobile() {
            this.mobile = false;
            document.body.classList.remove('overflow-hidden');
        },

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
    @keydown.escape.window="closeNow(); closeMobile()"
    {{-- -mb-16 in overlay mode pulls the following section up by the bar's own
         height, so the hero starts at the top of the viewport and the sticky bar
         floats OVER it. Without this the bar sits above the hero in normal flow,
         and going transparent just exposes the white page behind it. --}}
    @class(['sticky top-0 z-50', '-mb-16' => $overlay])
>
    {{-- Desktop dropdown backdrop. Sits under the bar and panel but over the
         page, so content behind an open menu recedes. --}}
    <div x-show="menu" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="closeNow()"
        class="fixed inset-0 top-16 -z-10 bg-ink/20 backdrop-blur-sm"
        aria-hidden="true"></div>

    {{-- relative z-50 so the bar paints ABOVE the z-40 full-screen menu inside
         this same stacking context — that is what keeps the hamburger reachable
         as the close control while the menu is open. --}}
    <div class="relative z-50 transition-colors duration-500"
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

                {{-- The four items (desktop) --}}
                <div class="hidden md:flex items-center gap-1" @mouseleave="scheduleClose()">
                    @foreach ($links as $link)
                        @if (isset($link['menu']))
                            <div class="relative" @mouseenter="open('{{ $link['label'] }}')">
                                <button type="button"
                                    @click="menu === '{{ $link['label'] }}' ? closeNow() : open('{{ $link['label'] }}')"
                                    @focus="open('{{ $link['label'] }}')"
                                    :aria-expanded="menu === '{{ $link['label'] }}' ? 'true' : 'false'"
                                    aria-haspopup="true"
                                    class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium transition-colors rounded-card"
                                    :class="onHero
                                        ? (menu === '{{ $link['label'] }}' ? 'text-white' : 'text-white/70 hover:text-white')
                                        : (menu === '{{ $link['label'] }}' ? 'text-ink' : 'text-muted hover:text-ink')">
                                    {{ $link['label'] }}
                                    <x-phosphor-caret-down class="w-3.5 h-3.5 transition-transform duration-300"
                                        ::class="menu === '{{ $link['label'] }}' && 'rotate-180'" />
                                </button>
                            </div>
                        @else
                            @php $current = request()->routeIs($link['route']); @endphp
                            <a href="{{ route($link['route']) }}"
                                @mouseenter="scheduleClose()"
                                @if ($current) aria-current="page" @endif
                                class="px-3 py-2 text-sm font-medium transition-colors rounded-card"
                                :class="onHero
                                    ? '{{ $current ? 'text-white' : 'text-white/70 hover:text-white' }}'
                                    : '{{ $current ? 'text-ink' : 'text-muted hover:text-ink' }}'">
                                {{ $link['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Actions (desktop) --}}
                {{-- No @auth branch.

                     There is no authenticated state on the central domain: the
                     `web` guard resolves to Staff, whose table lives in a
                     TENANT database and does not exist centrally. @auth is
                     therefore always false here — and worse, a session that
                     ever carried a user id would make auth()->check() query a
                     missing table and 500 every marketing page. Dashboard also
                     could not be linked even in principle: route('dashboard')
                     needs a {clinic}, and central does not know which. --}}
                <div class="hidden md:flex items-center gap-3">
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
                </div>

                {{-- Hamburger. Sits above the overlay (the bar is z-50, the panel
                     z-40) so it stays the close control while the menu is open. --}}
                <button type="button" @click="toggleMobile()"
                    class="md:hidden relative z-10 transition-colors -mr-1"
                    :class="onHero ? 'text-white/80 hover:text-white' : 'text-muted hover:text-ink'"
                    :aria-expanded="mobile ? 'true' : 'false'"
                    aria-controls="marketing-mobile-nav">
                    <span class="sr-only" x-text="mobile ? 'Close navigation' : 'Open navigation'">Open navigation</span>
                    <x-phosphor-list x-show="!mobile" class="w-6 h-6" />
                    <x-phosphor-x x-show="mobile" x-cloak class="w-6 h-6" />
                </button>
            </div>
        </nav>

        {{-- Desktop dropdown panel. Rendered outside the flex row so it can span
             the full container width without affecting the bar's layout. --}}
        @foreach ($links as $link)
            @continue (! isset($link['menu']))

            <div x-show="menu === '{{ $link['label'] }}'" x-cloak
                @mouseenter="open('{{ $link['label'] }}')" @mouseleave="scheduleClose()"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="hidden md:block absolute inset-x-0 top-16 bg-page border-b border-line">

                <div class="max-w-7xl mx-auto px-6 sm:px-8 py-8">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                        @foreach ($link['menu'] as $item)
                            <a href="{{ $item['href'] }}" @click="closeNow()"
                                class="group flex items-start gap-3 p-3 rounded-card hover:bg-warm transition-colors">
                                <span class="bg-warm border border-line rounded-card p-2 shrink-0 transition-colors group-hover:bg-page">
                                    <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-ink" aria-hidden="true" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-ink">{{ $item['label'] }}</span>
                                    <span class="block text-xs text-muted mt-0.5">{{ $item['desc'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{--
        Mobile menu — a full-screen overlay, not a drawer in the document flow.

        `fixed inset-0` is the whole point: the previous version was an in-flow
        block under the bar, so opening it pushed the entire page down and
        closing it snapped everything back. Fixed positioning takes it out of
        flow entirely, so the page beneath does not move at all.

        z-40 puts it under the bar (z-50) so the hamburger stays visible and
        becomes the close button, and the brand stays on screen.
    --}}
    <div id="marketing-mobile-nav" x-show="mobile" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="md:hidden fixed inset-0 z-40 bg-page/85 backdrop-blur-xl">

        {{-- pt-16 clears the bar. The column pins the actions to the bottom, so
             the links sit at the top-left and the two CTAs are a thumb-reach
             away regardless of how many links there are. --}}
        <div class="h-full overflow-y-auto flex flex-col pt-16">
            <nav class="flex-1 px-6 py-8" aria-label="Mobile">
                @foreach ($links as $link)
                    @if (isset($link['menu']))
                        <div class="border-b border-line">
                            <button type="button"
                                @click="mobileGroup = (mobileGroup === '{{ $link['label'] }}' ? null : '{{ $link['label'] }}')"
                                :aria-expanded="mobileGroup === '{{ $link['label'] }}' ? 'true' : 'false'"
                                aria-controls="mobile-group-{{ Str::slug($link['label']) }}"
                                class="w-full flex items-center justify-between py-4 text-2xl font-medium text-ink tracking-tight text-left">
                                {{ $link['label'] }}
                                <x-phosphor-caret-down class="w-5 h-5 text-muted transition-transform duration-300"
                                    ::class="mobileGroup === '{{ $link['label'] }}' && 'rotate-180'" />
                            </button>

                            {{-- grid-rows 0fr→1fr eases the height without needing
                                 a measured pixel value, the same technique the FAQ
                                 uses. --}}
                            <div id="mobile-group-{{ Str::slug($link['label']) }}"
                                class="grid transition-[grid-template-rows] duration-500 ease-out"
                                :class="mobileGroup === '{{ $link['label'] }}' ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                                <div class="overflow-hidden">
                                    <div class="flex flex-col gap-1 pb-4">
                                        @foreach ($link['menu'] as $item)
                                            <a href="{{ $item['href'] }}" @click="closeMobile()"
                                                class="flex items-center gap-3 py-2.5 text-base text-muted hover:text-ink transition-colors">
                                                <x-dynamic-component :component="$item['icon']" class="w-5 h-5 shrink-0" aria-hidden="true" />
                                                {{ $item['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route($link['route']) }}" @click="closeMobile()"
                            @if (request()->routeIs($link['route'])) aria-current="page" @endif
                            @class([
                                'flex items-center justify-between py-4 border-b border-line text-2xl font-medium tracking-tight',
                                'text-ink' => request()->routeIs($link['route']),
                                'text-muted' => ! request()->routeIs($link['route']),
                            ])>
                            {{ $link['label'] }}
                            @if (request()->routeIs($link['route']))
                                <span class="w-1.5 h-1.5 rounded-full bg-accent" aria-hidden="true"></span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Actions, separated at the foot of the screen. --}}
            <div class="px-6 pb-8 pt-6 border-t border-line bg-page/60">
                <div class="flex flex-col gap-3">
                    <a href="{{ route('signup') }}" @click="closeMobile()"
                        class="bg-ink text-white rounded-full px-5 py-3.5 text-sm font-medium hover:bg-ink/90 transition-colors text-center">
                        Get started
                    </a>
                    <a href="{{ route('demo') }}" @click="closeMobile()"
                        class="border border-line text-ink rounded-full px-4 py-3.5 text-sm font-medium hover:bg-warm transition-colors text-center">
                        Book a demo
                    </a>
                </div>

                {{-- Staff entry point. Not a login link: login lives on the
                     clinic's own subdomain, and this is how someone gets there
                     when they do not know the address. --}}
                <p class="text-center text-sm text-muted mt-5">
                    Clinic staff?
                    <a href="{{ route('find-clinic') }}" @click="closeMobile()"
                        class="text-ink font-medium hover:underline">Find your clinic</a>
                </p>
            </div>
        </div>
    </div>
</header>
