@props(['overlay' => false])

@php
    /*
     * Menu data lives here so the desktop dropdowns and the mobile accordion
     * render from one source and can never drift apart.
     *
     * The /features anchors don't exist yet — that page is still a placeholder.
     * They resolve to the page itself rather than a 404, and the ids get added
     * when Features is built.
     */
    $menus = [
        'Product' => [
            'items' => [
                ['label' => 'Patient records', 'desc' => 'One record, full visit history', 'icon' => 'phosphor-clipboard-text', 'href' => route('features').'#patient-records'],
                ['label' => 'Live queue', 'desc' => 'Who is waiting, right now', 'icon' => 'phosphor-clock', 'href' => route('features').'#live-queue'],
                ['label' => 'Consultations & vitals', 'desc' => 'Notes, diagnosis, plan', 'icon' => 'phosphor-stethoscope', 'href' => route('features').'#consultations'],
                ['label' => 'Prescriptions', 'desc' => 'Your drug list, your prices', 'icon' => 'phosphor-first-aid-kit', 'href' => route('features').'#prescriptions'],
                ['label' => 'Billing & receipts', 'desc' => 'Invoices that total themselves', 'icon' => 'phosphor-receipt', 'href' => route('features').'#billing'],
                ['label' => 'Audit & oversight', 'desc' => 'Every action, attributed', 'icon' => 'phosphor-chart-line', 'href' => route('features').'#audit'],
            ],
            'footer' => ['label' => 'See pricing', 'href' => route('pricing')],
        ],
        'Company' => [
            'items' => [
                ['label' => 'About', 'desc' => 'Built in Port Harcourt', 'icon' => 'phosphor-users-three', 'href' => route('about')],
                ['label' => 'Book a demo', 'desc' => 'See it on your workflow', 'icon' => 'phosphor-whatsapp-logo', 'href' => route('demo')],
                ['label' => 'Privacy', 'desc' => 'How we handle data', 'icon' => 'phosphor-lock-key', 'href' => route('legal.privacy')],
                ['label' => 'Terms', 'desc' => 'Terms of service', 'icon' => 'phosphor-shield-check', 'href' => route('legal.terms')],
            ],
            'footer' => null,
        ],
    ];
@endphp

{{--
    Public marketing navigation.

    Structure from the customer.io reference: brand left, menus centred, CTAs
    right — in our palette, so a plain ink pill rather than a glowing green one.

    Only two CTAs: signing in is reachable from the Get started page and from
    the footer, so the bar stays uncluttered.

    Accessibility note: hover alone would lock out keyboard and touch users, so
    each trigger is a real <button> that also opens on focus and Enter/Space,
    closes on Escape, and the panel is reachable by Tab. The close is delayed
    ~150ms so the pointer can cross the gap into the panel without losing it.
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
         * The bar goes solid whenever a menu is open — otherwise a white
         * dropdown panel would hang off a transparent bar. Previously only the
         * BACKGROUND accounted for that, while every text binding checked
         * `solid` alone, so opening a dropdown over the hero left white text on
         * a white bar. Everything now reads this one getter.
         */
        get onHero() { return ! this.solid && ! this.menu && ! this.mobile },

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
    @keydown.escape.window="closeNow(); mobile = false"
    {{-- -mb-16 in overlay mode pulls the following section up by the bar's own
         height, so the hero starts at the top of the viewport and the sticky bar
         floats OVER it. Without this the bar sits above the hero in normal flow,
         and going transparent just exposes the white page behind it. --}}
    @class(['sticky top-0 z-50', '-mb-16' => $overlay])
>
    {{-- Backdrop. Sits under the bar and panel but over the page, so the content
         behind a open menu blurs back and the panel reads clearly. --}}
    <div x-show="menu" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="closeNow()"
        class="fixed inset-0 top-16 -z-10 bg-ink/20 backdrop-blur-sm"
        aria-hidden="true"></div>

    <div class="transition-colors duration-300"
        :class="onHero
            ? 'bg-transparent border-b border-transparent nav-overlay'
            : 'bg-page/95 backdrop-blur border-b border-line'">
        <nav class="max-w-7xl mx-auto px-6 sm:px-8" aria-label="Main">
            <div class="flex items-center justify-between h-16">

                {{-- Brand --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 rounded-card">
                    <img src="{{ asset('images/africhart-logo.svg') }}" alt=""
                        class="w-8 h-8 transition-[filter] duration-300" :class="onHero && 'invert'">
                    <span class="text-lg font-medium tracking-tight transition-colors duration-300"
                        :class="onHero ? 'text-white' : 'text-ink'">AfriChart</span>
                </a>

                {{-- Menus (desktop) --}}
                <div class="hidden md:flex items-center gap-1" @mouseleave="scheduleClose()">
                    @foreach ($menus as $name => $menu)
                        <div class="relative" @mouseenter="open('{{ $name }}')">
                            <button type="button"
                                @click="menu === '{{ $name }}' ? closeNow() : open('{{ $name }}')"
                                @focus="open('{{ $name }}')"
                                :aria-expanded="menu === '{{ $name }}' ? 'true' : 'false'"
                                aria-haspopup="true"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium transition-colors rounded-card"
                                :class="onHero
                                    ? (menu === '{{ $name }}' ? 'text-white' : 'text-white/70 hover:text-white')
                                    : (menu === '{{ $name }}' ? 'text-ink' : 'text-muted hover:text-ink')">
                                {{ $name }}
                                <x-phosphor-caret-down class="w-3.5 h-3.5 transition-transform duration-200"
                                    ::class="menu === '{{ $name }}' && 'rotate-180'" />
                            </button>
                        </div>
                    @endforeach

                    <a href="{{ route('pricing') }}"
                        @mouseenter="scheduleClose()"
                        class="px-3 py-2 text-sm font-medium transition-colors rounded-card"
                        :class="onHero
                            ? '{{ request()->routeIs('pricing') ? 'text-white' : 'text-white/70 hover:text-white' }}'
                            : '{{ request()->routeIs('pricing') ? 'text-ink' : 'text-muted hover:text-ink' }}'">
                        Pricing
                    </a>
                </div>

                {{-- CTAs (desktop) --}}
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                            Dashboard
                        </a>
                    @else
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

        {{-- Dropdown panels. Rendered outside the flex row so they can span the
             full container width without affecting the bar's layout. --}}
        @foreach ($menus as $name => $menu)
            <div x-show="menu === '{{ $name }}'" x-cloak
                @mouseenter="open('{{ $name }}')" @mouseleave="scheduleClose()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="hidden md:block absolute inset-x-0 top-16 bg-page border-b border-line">

                <div class="max-w-7xl mx-auto px-6 sm:px-8 py-8">
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach ($menu['items'] as $item)
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

                    @if ($menu['footer'])
                        <div class="mt-6 pt-5 border-t border-line">
                            <a href="{{ $menu['footer']['href'] }}" @click="closeNow()"
                                class="group inline-flex items-center gap-1.5 text-sm font-medium text-ink hover:underline">
                                {{ $menu['footer']['label'] }}
                                <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Mobile panel: the same menus as collapsible groups. No hover here. --}}
        <div id="marketing-mobile-nav" x-show="mobile" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="md:hidden border-t border-line bg-page px-6 py-4 max-h-[calc(100svh-4rem)] overflow-y-auto">

            @foreach ($menus as $name => $menu)
                <div class="border-b border-line py-1">
                    <button type="button"
                        @click="mobileGroup = (mobileGroup === '{{ $name }}' ? null : '{{ $name }}')"
                        :aria-expanded="mobileGroup === '{{ $name }}' ? 'true' : 'false'"
                        aria-controls="mobile-group-{{ Str::slug($name) }}"
                        class="w-full flex items-center justify-between py-2.5 text-sm font-medium text-ink">
                        {{ $name }}
                        <x-phosphor-caret-down class="w-4 h-4 text-muted transition-transform duration-200"
                            ::class="mobileGroup === '{{ $name }}' && 'rotate-180'" />
                    </button>

                    <div id="mobile-group-{{ Str::slug($name) }}" x-show="mobileGroup === '{{ $name }}'" x-cloak
                        class="flex flex-col gap-1 pb-2">
                        @foreach ($menu['items'] as $item)
                            <a href="{{ $item['href'] }}" @click="mobile = false"
                                class="px-3 py-2.5 rounded-card text-sm text-muted hover:bg-warm hover:text-ink transition-colors">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <a href="{{ route('pricing') }}" @click="mobile = false"
                class="block py-3 border-b border-line text-sm font-medium text-ink">Pricing</a>

            <div class="flex flex-col gap-3 mt-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors text-center">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('signup') }}"
                        class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors text-center">
                        Get started
                    </a>
                    <a href="{{ route('demo') }}"
                        class="border border-line text-ink rounded-full px-4 py-2.5 text-sm font-medium hover:bg-warm transition-colors text-center">
                        Book a demo
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
