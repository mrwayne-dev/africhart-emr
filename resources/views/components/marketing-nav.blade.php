{{--
    Public marketing navigation.

    Structure follows the customer.io reference: brand left, links centred,
    "Sign in" + dual CTA right. Palette is entirely ours — a plain ink pill
    rather than the reference's glowing green one.

    Renders for guests and signed-in staff alike; the right-hand side swaps
    "Sign in" for "Dashboard" instead of the page redirecting.
--}}
<header
    x-data="{ open: false }"
    class="sticky top-0 z-50 bg-page/95 backdrop-blur border-b border-line"
>
    <nav class="max-w-5xl mx-auto px-6 sm:px-8" aria-label="Main">
        <div class="flex items-center justify-between h-16">

            {{-- Brand --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 rounded-card">
                <img src="{{ asset('images/africhart-logo.svg') }}" alt="" class="w-8 h-8">
                <span class="text-lg font-medium text-ink tracking-tight">AfriChart</span>
            </a>

            {{-- Centre links (desktop) --}}
            <div class="hidden md:flex items-center gap-8">
                @foreach ([
                    ['route' => 'features', 'label' => 'Features'],
                    ['route' => 'pricing', 'label' => 'Pricing'],
                    ['route' => 'about', 'label' => 'About'],
                ] as $link)
                    <a href="{{ route($link['route']) }}"
                        @class([
                            'text-sm font-medium transition-colors',
                            'text-ink' => request()->routeIs($link['route']),
                            'text-muted hover:text-ink' => ! request()->routeIs($link['route']),
                        ])
                        @if (request()->routeIs($link['route'])) aria-current="page" @endif>
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
                    <a href="{{ route('login') }}" class="text-sm font-medium text-muted hover:text-ink transition-colors">
                        Sign in
                    </a>
                    <a href="{{ route('demo') }}"
                        class="inline-flex items-center border border-line text-ink rounded-full px-4 py-2 text-sm font-medium hover:bg-warm transition-colors">
                        Book a demo
                    </a>
                    <a href="{{ route('signup') }}"
                        class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                        Get started
                    </a>
                @endauth
            </div>

            {{-- Hamburger --}}
            <button type="button" @click="open = !open"
                class="md:hidden text-muted hover:text-ink transition-colors -mr-1"
                :aria-expanded="open ? 'true' : 'false'"
                aria-controls="marketing-mobile-nav">
                <span class="sr-only">Toggle navigation</span>
                <x-phosphor-list x-show="!open" class="w-6 h-6" />
                <x-phosphor-x x-show="open" x-cloak class="w-6 h-6" />
            </button>
        </div>

        {{-- Mobile panel. Expands in flow rather than as an overlay drawer so it
             never traps focus behind a backdrop. --}}
        <div id="marketing-mobile-nav" x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="md:hidden border-t border-line py-4">

            <div class="flex flex-col gap-1">
                @foreach ([
                    ['route' => 'features', 'label' => 'Features'],
                    ['route' => 'pricing', 'label' => 'Pricing'],
                    ['route' => 'about', 'label' => 'About'],
                ] as $link)
                    <a href="{{ route($link['route']) }}" @click="open = false"
                        @class([
                            'px-3 py-2.5 rounded-card text-sm font-medium transition-colors',
                            'bg-warm text-ink' => request()->routeIs($link['route']),
                            'text-muted hover:bg-warm hover:text-ink' => ! request()->routeIs($link['route']),
                        ])>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 mt-4 pt-4 border-t border-line">
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
                    <a href="{{ route('login') }}"
                        class="text-sm font-medium text-muted hover:text-ink transition-colors text-center py-1">
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </nav>
</header>
