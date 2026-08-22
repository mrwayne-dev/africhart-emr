@props([
    'search' => false,   // the clinic-address finder — Home only
])

{{--
    Closing CTA, architecture from the Squarespace reference: a centred mark, a
    two-line heading, a quiet one-line reassurance, then two cards side by side.

    The search block below it is opt-in. It reads as the reference's
    "find the perfect domain" moment, adapted to what we actually sell: every
    clinic gets its own subdomain, so the useful question is what yours will be.
--}}
<div class="text-center max-w-3xl mx-auto" data-reveal>
    <span class="inline-flex items-center justify-center w-10 h-10 rounded-card bg-ink mb-8">
        <x-phosphor-first-aid-kit class="w-5 h-5 text-white" aria-hidden="true" />
    </span>

    <h2 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.05]">
        Managing records has never<br class="hidden sm:block"> been easier than with AfriChart
    </h2>

    <p class="text-base text-muted mt-5">No paperwork, no training week, no IT department.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-14 max-w-4xl mx-auto">
    @foreach ([
        ['href' => route('signup'), 'icon' => 'phosphor-first-aid-kit', 'title' => 'Start free',
         'body' => 'Thirty days, no card. We set your clinic up and walk your staff through day one.',
         'cta' => 'Get started', 'delay' => 0],
        ['href' => route('demo'), 'icon' => 'phosphor-envelope-simple', 'title' => 'See it first',
         'body' => 'Fifteen minutes on your own workflow, with someone in Port Harcourt.',
         'cta' => 'Book a demo', 'delay' => 80],
    ] as $card)
        <a href="{{ $card['href'] }}"
            class="group bg-ink rounded-card p-8 flex flex-col justify-between min-h-[15rem] text-left
                transition-all duration-200 hover:-translate-y-0.5 hover:bg-ink-body"
            data-reveal data-reveal-delay="{{ $card['delay'] }}">
            <span class="bg-page/10 rounded-card p-2.5 w-fit">
                <x-dynamic-component :component="$card['icon']" class="w-5 h-5 text-white" aria-hidden="true" />
            </span>

            <span class="mt-10">
                <span class="block text-xl font-medium text-white tracking-tight">{{ $card['title'] }}</span>
                <span class="block text-sm text-white/60 mt-2 leading-relaxed">{{ $card['body'] }}</span>
                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-white mt-5">
                    {{ $card['cta'] }}
                    <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                </span>
            </span>
        </a>
    @endforeach
</div>

@if ($search)
    {{--
        Clinic address finder.

        This does NOT claim to check availability — there is no clinic registry
        until the tenancy work lands, and a field that always says "available"
        would be a lie told at the exact moment someone decides to trust us.
        It carries the name through to sign-up, prefilled, which is honest and
        still saves the visitor a step.
    --}}
    <div class="bg-ink-body rounded-card px-6 py-14 sm:px-12 sm:py-20 mt-16" data-reveal>
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-medium text-white tracking-tight leading-[1.05]">
                Find the right address<br class="hidden sm:block"> for your clinic
            </h2>
            <p class="text-sm text-white/60 mt-4">
                Every clinic runs on its own subdomain, with its own database behind it.
            </p>

            <form action="{{ route('signup') }}" method="GET" class="mt-9">
                <div class="flex items-center gap-2 bg-white/5 border border-white/15 rounded-full
                        pl-5 pr-2 py-2 focus-within:border-white/40 transition-colors">
                    <x-phosphor-magnifying-glass class="w-4 h-4 text-white/40 shrink-0" aria-hidden="true" />

                    <label for="clinic-address" class="sr-only">Your clinic name</label>
                    <input id="clinic-address" name="clinic" type="text" autocomplete="organization"
                        placeholder="yourclinic"
                        class="flex-1 min-w-0 bg-transparent text-white placeholder:text-white/35
                            text-sm sm:text-base focus:outline-none">

                    <span class="hidden sm:inline text-sm text-white/40 shrink-0">.africhartemr.com</span>

                    <button type="submit"
                        class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full
                            bg-page text-ink hover:bg-warm transition-colors">
                        <span class="sr-only">Continue with this clinic name</span>
                        <x-phosphor-arrow-right class="w-4 h-4" aria-hidden="true" />
                    </button>
                </div>
            </form>

            <p class="text-xs text-white/45 mt-5">
                Already set up?
                <a href="{{ route('login') }}" class="text-white hover:underline">Sign in to your clinic</a>
            </p>
        </div>
    </div>
@endif
