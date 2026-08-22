@extends('layouts.marketing')

@section('title', 'AfriChart — Clinic management software for Nigerian clinics')
@section('nav_overlay', true)
@section('description', 'See every patient, every prescription and every naira in your clinic — from anywhere. Built in Port Harcourt for Nigerian private clinics.')

@php
    /*
     * Hero background. Detect whichever extension was supplied rather than
     * hard-coding one, so dropping in a .webp or .png just works. When no file
     * exists the section falls back to the flat ink panel underneath — the page
     * never breaks waiting on an asset.
     */
    $heroImage = collect(['webp', 'jpg', 'jpeg', 'png'])
        ->map(fn ($ext) => "images/africhart-home.{$ext}")
        ->first(fn ($path) => file_exists(public_path($path)));

    $featured = collect($tiers)->firstWhere('featured', true);
    $otherTiers = collect($tiers)->where('featured', false)->values();
@endphp

@section('content')

    {{-- ========================= HERO ========================= --}}
    {{--
        Full-bleed textural background, content anchored to the bottom rather
        than centred. `svh` not `vh` so mobile browser chrome can't clip it;
        `min-h` not `h` so a short viewport grows instead of cropping.
    --}}
    <section data-nav-overlay-anchor
        class="relative min-h-[100svh] flex flex-col justify-end overflow-hidden bg-ink-body">

        @if ($heroImage)
            <img src="{{ asset($heroImage) }}" alt=""
                class="absolute inset-0 w-full h-full object-cover hero-settle" aria-hidden="true">
        @endif

        {{-- Two-stop gradient: darkest at the lower-left where the headline
             sits, opening up toward the upper-right so the image still reads. --}}
        <div class="absolute inset-0 bg-gradient-to-tr from-ink-body via-ink-body/75 to-ink-body/30" aria-hidden="true"></div>

        <div class="relative max-w-7xl mx-auto px-6 sm:px-8 w-full pt-28 pb-8">

            {{-- Accent 1 of 3: the brackets only. First impression, brand
                 signal, and it spends almost no attention to do it. --}}
            <p class="font-mono text-xs text-white/60 uppercase tracking-[0.2em] mb-6" data-reveal>
                <span class="text-accent">[</span> For Nigerian clinics <span class="text-accent">]</span>
            </p>

            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-10 lg:gap-16">
                <h1 class="font-medium text-white tracking-tight leading-[0.95] max-w-4xl
                        text-[clamp(2.25rem,6.5vw,5.5rem)]"
                    data-reveal data-reveal-delay="100">
                    See everything<br>your clinic does.
                </h1>

                <p class="text-base text-white/70 leading-relaxed max-w-sm lg:text-right lg:pb-3"
                    data-reveal data-reveal-delay="220">
                    Front desk, consulting room and billing in one place — so the queue moves,
                    nothing goes unbilled, and you can check on the clinic from anywhere.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-10" data-reveal data-reveal-delay="320">
                <a href="{{ route('signup') }}"
                    class="group inline-flex items-center justify-center gap-2 bg-page text-ink rounded-full px-7 py-3.5 text-sm font-medium hover:bg-warm transition-colors">
                    Get started
                    <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                </a>
                <a href="{{ route('demo') }}"
                    class="inline-flex items-center justify-center border border-white/30 text-white rounded-full px-7 py-3.5 text-sm font-medium hover:bg-white/10 hover:border-white/50 transition-colors">
                    Book a demo
                </a>
            </div>

            {{-- Meta bar. The clock is Lagos time, not the visitor's — the point
                 is "we're in your timezone", so a clinic owner sees their own. --}}
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3 mt-12 pt-5 border-t border-white/15
                    font-mono text-xs text-white/60 uppercase tracking-[0.15em]"
                data-reveal data-reveal-delay="420">
                <span>4 staff roles</span>
                <span>Port Harcourt built</span>
                <span>Naira pricing</span>
                <span class="ml-auto" x-data="lagosClock">
                    [ <span x-text="now">00:00:00</span> WAT ]
                </span>
            </div>
        </div>
    </section>

    {{-- ========================= HOW IT WORKS ========================= --}}
    {{-- Editorial flow, not a row of cards: numeral, role, copy, hairline. --}}
    <x-marketing-section tone="page">
        <div data-reveal>
            <x-marketing-heading
                eyebrow="How it works"
                title="One flow, front desk to owner."
                lead="Every role sees exactly what they need, and each step hands cleanly to the next." />
        </div>

        <div class="border-t border-line">
            @foreach ([
                ['role' => 'Reception', 'text' => 'Registers the patient and checks them into today\'s queue.'],
                ['role' => 'Nurse', 'text' => 'Records vitals while the patient waits — before the doctor opens anything.'],
                ['role' => 'Doctor', 'text' => 'Consults, diagnoses and prescribes, with the vitals already there.'],
                ['role' => 'Reception', 'text' => 'The invoice totals itself from the visit. Take payment, print the receipt.'],
                ['role' => 'Owner', 'text' => 'Sees all of it — every consultation, every invoice, every change — without standing in the clinic.'],
            ] as $i => $step)
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-10 py-7 border-b border-line
                        transition-colors hover:bg-warm/60 -mx-4 px-4"
                    data-reveal data-reveal-delay="{{ $i * 70 }}">
                    <span class="font-mono text-xs text-muted shrink-0 sm:w-16 tracking-widest">[ 0{{ $i + 1 }} ]</span>
                    <h3 class="text-xl sm:text-2xl font-medium text-ink tracking-tight shrink-0 sm:w-56">{{ $step['role'] }}</h3>
                    <p class="text-base text-muted leading-relaxed max-w-2xl">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </x-marketing-section>

    {{-- ========================= STAT BAND ========================= --}}
    {{-- A tight beat between two heavy sections, and the only place the third
         surface tone is used — it breaks the page/warm flip-flop. --}}
    <x-marketing-section tone="warm-alt" size="tight">
        <x-stat-band :stats="[
            ['value' => 4, 'label' => 'Staff roles, each seeing only what they need'],
            ['value' => 5, 'label' => 'Steps from front desk to the owner\'s phone'],
            ['value' => 1, 'label' => 'Database per clinic — isolation by construction'],
            ['value' => 30, 'label' => 'Days free, no card required'],
        ]" />
    </x-marketing-section>

    {{-- ========================= WHAT YOU GET (STICKY SHOWCASE) ========================= --}}
    {{--
        Feature list left, sticky visual right that swaps as you scroll.
        Below lg the sticky column is dropped entirely and each feature renders
        its own visual inline, so the section degrades to a readable stack.
    --}}
    @php
        $showcase = [
            ['key' => 'records', 'title' => 'Patient records', 'icon' => 'phosphor-clipboard-text',
             'text' => 'One record per patient with the full visit timeline. Nobody has to ask what happened last time.'],
            ['key' => 'queue', 'title' => 'Live queue', 'icon' => 'phosphor-clock',
             'text' => 'Who is waiting, who is being seen, and for how long. The board updates itself, so nobody walks to the front desk to ask.'],
            ['key' => 'consult', 'title' => 'Consultations & vitals', 'icon' => 'phosphor-stethoscope',
             'text' => 'Notes, diagnosis and plan, with the nurse\'s vitals already attached — and prescriptions from your own drug list, at your own prices.'],
            ['key' => 'billing', 'title' => 'Billing & receipts', 'icon' => 'phosphor-receipt',
             'text' => 'Invoices total themselves from the visit. PDF receipts, cash or transfer, nothing quietly missed.'],
            ['key' => 'audit', 'title' => 'Audit & oversight', 'icon' => 'phosphor-chart-line',
             'text' => 'Every action attributed to a person, and a dashboard that shows the week at a glance.'],
        ];
    @endphp

    <x-marketing-section tone="warm" size="tall">
        <div data-reveal>
            <x-marketing-heading
                eyebrow="What you get"
                title="Everything a clinic actually does."
                lead="Not a hospital suite bolted down to fit. The workflow a private clinic runs every day." />
        </div>

        <div x-data="featureShowcase" class="lg:grid lg:grid-cols-2 lg:gap-16 xl:gap-24">

            {{-- List --}}
            <div>
                @foreach ($showcase as $i => $item)
                    <div data-showcase-item="{{ $i }}"
                        class="py-8 lg:py-16 border-b border-line last:border-b-0">

                        <div class="flex items-center gap-3">
                            <x-dynamic-component :component="$item['icon']"
                                class="w-5 h-5 shrink-0 transition-colors"
                                ::class="active === {{ $i }} ? 'text-ink' : 'text-muted'" aria-hidden="true" />
                            <h3 class="text-xl sm:text-2xl font-medium tracking-tight transition-colors"
                                ::class="active === {{ $i }} ? 'text-ink' : 'text-muted'">
                                {{ $item['title'] }}
                            </h3>
                        </div>

                        <p class="text-base text-muted leading-relaxed mt-3 max-w-md">{{ $item['text'] }}</p>

                        {{-- Progress rule — marks the active item on desktop. --}}
                        <div class="hidden lg:block h-px bg-line mt-6 overflow-hidden">
                            {{-- Accent 3 of 3: the only element that moves as you scroll, so
                                 accent makes "you are here" legible at a glance. --}}
                            <div class="h-full bg-accent origin-left transition-transform duration-500 ease-out"
                                ::class="active === {{ $i }} ? 'scale-x-100' : 'scale-x-0'"></div>
                        </div>

                        {{-- Mobile: the visual lives with its feature. --}}
                        <div class="lg:hidden mt-6">
                            @include('marketing.partials.showcase-visual', ['key' => $item['key']])
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sticky visual (desktop only).

                 All five panels are stacked in a SINGLE grid cell and toggled by
                 opacity, never by display. Previously they were siblings in normal
                 flow with x-show: during a swap the leaving panel still occupied
                 space for its 150ms leave transition while the entering one was
                 already inserted, so the sticky box's height doubled and then
                 collapsed — a visible jump on every change. Keeping every panel
                 rendered means the grid row is always sized to the tallest one, so
                 the height never moves and the swap is a clean crossfade. --}}
            <div class="hidden lg:block">
                <div class="sticky top-28 grid">
                    @foreach ($showcase as $i => $item)
                        <div class="[grid-area:1/1] transition-opacity duration-300 ease-out"
                            ::class="active === {{ $i }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                            ::aria-hidden="active === {{ $i }} ? 'false' : 'true'">
                            @include('marketing.partials.showcase-visual', ['key' => $item['key']])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= YOUR DATA ========================= --}}
    <x-marketing-section tone="page">
        <div class="max-w-3xl" data-reveal>
            <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">Your data</p>
            <h2 class="text-3xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.05]">
                Separate by construction, not by a filter someone could forget.
            </h2>
        </div>

        <div class="mt-12">
            <x-trust-strip :items="[
                ['icon' => 'phosphor-database', 'name' => 'Per-clinic database', 'sub' => 'Isolated'],
                ['icon' => 'phosphor-lock-key', 'name' => 'Backups', 'sub' => 'Encrypted'],
                ['icon' => 'phosphor-clipboard-text', 'name' => 'Audit trail', 'sub' => 'Every action'],
                ['icon' => 'phosphor-shield-check', 'name' => 'NDPA', 'sub' => 'Aligned'],
                ['icon' => 'phosphor-users-three', 'name' => 'Support', 'sub' => 'Port Harcourt'],
            ]" />
        </div>

        <div class="lg:grid lg:grid-cols-12 lg:gap-16 mt-16">
            <p class="text-base text-muted leading-relaxed lg:col-span-5" data-reveal>
                Not a shared table with a column telling clinics apart. Each clinic gets its own
                database, so one clinic's records cannot be reached from another — and we can say
                exactly that, in one sentence, to anyone who asks.
            </p>

            <div class="lg:col-span-7 mt-10 lg:mt-0 border-t border-line">
                @foreach ([
                    ['title' => 'One database per clinic', 'text' => 'There is no query that could return another clinic\'s patients, because their records are not in the same database.'],
                    ['title' => 'Encrypted, tested backups', 'text' => 'Daily backups, AES-256 encrypted, stored off the server — with a restore we have actually rehearsed rather than assumed.'],
                    ['title' => 'Built for the NDPA', 'text' => 'A documented data-isolation guarantee and a per-clinic data-processing agreement, so your compliance paperwork has something to point at.'],
                ] as $i => $item)
                    <div class="py-6 border-b border-line" data-reveal data-reveal-delay="{{ $i * 80 }}">
                        <h3 class="text-base font-medium text-ink tracking-tight">{{ $item['title'] }}</h3>
                        <p class="text-sm text-muted mt-2 leading-relaxed max-w-xl">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= PRICING ========================= --}}
    {{--
        One plan carries the section. The other two are a click away rather than
        competing for attention — the recommendation reads as a recommendation,
        not as the middle column of three.
    --}}
    <x-marketing-section tone="warm" size="tall">
        <div data-reveal>
            <x-marketing-heading
                align="center"
                eyebrow="Pricing"
                title="Naira pricing, stated plainly."
                lead="No dollar invoices, no surprise conversion. The setup fee is shown up front." />
        </div>

        <div x-data="{ others: false }" class="max-w-5xl mx-auto">

            <div data-reveal>
                <x-pricing-tier :tier="$featured" size="lg" />
            </div>

            <div class="text-center mt-8">
                <button type="button" @click="others = ! others"
                    :aria-expanded="others ? 'true' : 'false'"
                    aria-controls="other-plans"
                    class="group inline-flex items-center gap-2 border border-line bg-page text-ink rounded-full px-5 py-2.5 text-sm font-medium hover:bg-warm hover:border-muted/30 transition-colors">
                    <span x-text="others ? 'Hide other plans' : 'See other plans'">See other plans</span>
                    <x-phosphor-caret-down class="w-4 h-4 text-muted transition-transform duration-300"
                        ::class="others && 'rotate-180'" />
                </button>
            </div>

            {{-- Same grid-rows height technique as the FAQ. --}}
            <div id="other-plans"
                class="grid transition-[grid-template-rows] duration-500 ease-out"
                :class="others ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                <div class="overflow-hidden">
                    <div class="grid md:grid-cols-2 gap-5 pt-8">
                        @foreach ($otherTiers as $tier)
                            <x-pricing-tier :tier="$tier" />
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="text-center mt-10" data-reveal>
                <a href="{{ route('pricing') }}"
                    class="group inline-flex items-center gap-1.5 text-sm text-ink font-medium hover:underline">
                    Compare every plan in detail
                    <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                </a>
            </p>
        </div>
    </x-marketing-section>

    {{-- ========================= FAQ ========================= --}}
    <x-marketing-section tone="page">
        <div class="max-w-3xl mx-auto" data-reveal>
            <x-marketing-heading align="center" eyebrow="FAQ" title="Questions clinic owners ask" />

            <x-faq-accordion :items="[
                ['question' => 'Do I need to be technical to run this?',
                 'answer' => 'No. If your staff can use WhatsApp, they can use AfriChart. We set the clinic up for you, add your staff, and walk your team through their first day.'],
                ['question' => 'What happens to my records if I stop paying?',
                 'answer' => 'Your data is never deleted. The clinic goes read-only — everyone can still open and read every record, but nothing new can be written until the subscription is settled.'],
                ['question' => 'Can other clinics see my patients?',
                 'answer' => 'No. Every clinic runs on its own separate database, so there is no query that could return another clinic\'s records. That separation is structural, not a setting.'],
                ['question' => 'What if the internet goes down?',
                 'answer' => 'Actions that fail tell you plainly that they did not save, rather than failing silently — so nobody is left guessing whether a patient was registered.'],
                ['question' => 'Is there a setup fee?',
                 'answer' => 'Yes, once, and it is shown next to each plan. It covers configuring your clinic, loading your drug list and prices, and onboarding your staff.'],
                ['question' => 'Who do I call when something breaks?',
                 'answer' => 'Us, in Port Harcourt. Not an overseas ticket queue. Clinic and Group plans include WhatsApp support.'],
            ]" />
        </div>
    </x-marketing-section>

    {{-- ========================= CLOSING CTA ========================= --}}
    <x-marketing-section tone="warm">
        <x-cta-band search />
    </x-marketing-section>

@endsection
