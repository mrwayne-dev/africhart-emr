@extends('layouts.marketing')

@section('title', 'AfriChart — Clinic management software for Nigerian clinics')
@section('description', 'See every patient, every prescription and every naira in your clinic — from anywhere. Built in Port Harcourt for Nigerian private clinics.')

@section('content')

    {{-- ========================= HERO ========================= --}}
    {{--
        Full viewport below the sticky nav. `svh` not `vh` so mobile browser
        chrome doesn't clip it, and `min-h` not `h` so a short laptop viewport
        lets the hero grow instead of cropping the content.
    --}}
    <section class="bg-page relative min-h-[calc(100svh-4rem)] flex items-center py-16 sm:py-20">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 w-full">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">

                <div class="lg:col-span-6" data-reveal>
                    <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-5">
                        For Nigerian private clinics
                    </p>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium text-ink tracking-tight leading-[1.05]">
                        See every patient, every prescription, and every naira.
                    </h1>

                    <p class="text-base sm:text-lg text-muted mt-6 leading-relaxed max-w-xl">
                        AfriChart runs your front desk, consulting room and billing in one place —
                        so the queue moves, nothing goes unbilled, and you can check on the clinic
                        from anywhere.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 mt-9">
                        <a href="{{ route('signup') }}"
                            class="group inline-flex items-center justify-center gap-2 bg-ink text-white rounded-full px-7 py-3.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                            Get started
                            <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                        </a>
                        <a href="{{ route('demo') }}"
                            class="inline-flex items-center justify-center border border-line text-ink rounded-full px-7 py-3.5 text-sm font-medium hover:bg-warm hover:border-muted/30 transition-colors">
                            Book a demo
                        </a>
                    </div>

                    <ul class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-8">
                        @foreach (['30-day free trial', 'Naira pricing', 'Your own database'] as $point)
                            <li class="inline-flex items-center gap-1.5 text-xs text-muted">
                                <x-phosphor-check-circle class="w-4 h-4 text-ink" aria-hidden="true" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Product visual, built from real markup rather than a screenshot
                     so it stays truthful and sharp at any size. --}}
                <div class="lg:col-span-6" data-reveal data-reveal-delay="120">
                    <div class="bg-warm rounded-card border border-line p-5 sm:p-6" aria-hidden="true">
                        <div class="flex items-center justify-between mb-5">
                            <p class="text-sm font-medium text-ink tracking-tight">Today's queue</p>
                            <span class="inline-flex items-center gap-1.5 text-xs text-muted">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-ink opacity-60"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-ink"></span>
                                </span>
                                Live
                            </span>
                        </div>

                        <div class="flex flex-col gap-2.5">
                            @foreach ([
                                ['Chioma A. Nwosu', 'Fever · 08:42', 'In consultation'],
                                ['Emeka C. Obi', 'Follow-up · 08:55', 'Waiting'],
                                ['Fatima B. Mohammed', 'New complaint · 09:10', 'Waiting'],
                                ['Oluwaseun D. Adeyemi', 'Lab results · 09:24', 'Waiting'],
                            ] as [$name, $meta, $status])
                                <div class="bg-page border border-line rounded-card px-4 py-3 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-ink truncate">{{ $name }}</p>
                                        <p class="text-xs text-muted mt-0.5">{{ $meta }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shrink-0
                                        {{ $status === 'In consultation' ? 'bg-ink text-white' : 'bg-warm text-muted' }}">
                                        {{ $status }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between mt-5 pt-4 border-t border-line">
                            <p class="text-xs text-muted">Collected today</p>
                            <p class="text-lg font-medium text-ink tracking-tight">&#8358;86,500</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll cue. Hidden on short viewports where the hero already fills
             the screen without room to spare. --}}
        <div class="hidden lg:block absolute bottom-8 left-1/2 -translate-x-1/2" aria-hidden="true">
            <x-phosphor-caret-down class="w-5 h-5 text-muted animate-bounce" />
        </div>
    </section>

    {{-- ========================= HOW IT WORKS ========================= --}}
    <x-marketing-section tone="warm">
        <div data-reveal>
            <x-marketing-heading
                eyebrow="How it works"
                title="One flow, from the front desk to the owner's phone."
                lead="Every role sees exactly what they need, and each step hands cleanly to the next." />
        </div>

        <ol class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ([
                ['icon' => 'phosphor-users-three', 'role' => 'Reception', 'text' => 'Registers the patient and checks them into today\'s queue.'],
                ['icon' => 'phosphor-heartbeat', 'role' => 'Nurse', 'text' => 'Records vitals while the patient waits — before the doctor opens anything.'],
                ['icon' => 'phosphor-stethoscope', 'role' => 'Doctor', 'text' => 'Consults, diagnoses and prescribes, with the vitals already there.'],
                ['icon' => 'phosphor-receipt', 'role' => 'Reception', 'text' => 'Invoice totals itself from the visit. Take payment, print the receipt.'],
            ] as $i => $step)
                <li class="group bg-page border border-line rounded-card p-6 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted/30"
                    data-reveal data-reveal-delay="{{ $i * 80 }}">
                    <div class="flex items-center justify-between mb-5">
                        <x-dynamic-component :component="$step['icon']" class="w-6 h-6 text-ink" aria-hidden="true" />
                        <span class="text-xs font-semibold text-muted">0{{ $i + 1 }}</span>
                    </div>
                    <h3 class="text-base font-medium text-ink tracking-tight">{{ $step['role'] }}</h3>
                    <p class="text-sm text-muted mt-2 leading-relaxed">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>

        <p class="text-sm text-muted mt-8 max-w-2xl leading-relaxed" data-reveal>
            And the owner sees all of it — every consultation, every invoice, every change —
            without standing in the clinic.
        </p>
    </x-marketing-section>

    {{-- ========================= WHAT YOU GET (BENTO) ========================= --}}
    {{--
        Bento grid on a 6-column desktop track: 4+2 / 3+3 / 3+3. Span classes are
        written literally — Tailwind 4 scans for complete class names, so a
        computed "lg:col-span-{{ $n }}" would never be generated.
    --}}
    <x-marketing-section tone="page">
        <div data-reveal>
            <x-marketing-heading
                eyebrow="What you get"
                title="Everything a clinic actually does, in one system."
                lead="Not a hospital suite bolted down to fit. The workflow a private clinic runs every day." />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-5">

            <x-feature-cell title="Live queue" icon="phosphor-clock" tall
                class="sm:col-span-2 lg:col-span-4" data-reveal>
                Who is waiting, who is being seen, and for how long. The board updates itself, so
                nobody walks to the front desk to ask.

                <x-slot:visual>
                    <div class="w-full bg-warm border border-line rounded-card p-4">
                        <div class="flex flex-col gap-2">
                            @foreach ([['Waiting', '4'], ['In consultation', '1'], ['Ready to invoice', '2']] as [$label, $count])
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted">{{ $label }}</span>
                                    <span class="text-sm font-medium text-ink tabular-nums">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-slot:visual>
            </x-feature-cell>

            <x-feature-cell title="Patient records" icon="phosphor-clipboard-text"
                class="lg:col-span-2" data-reveal data-reveal-delay="80">
                One record per patient with a full visit timeline — never re-ask what happened last time.
            </x-feature-cell>

            <x-feature-cell title="Consultations & vitals" icon="phosphor-stethoscope"
                class="lg:col-span-3" data-reveal>
                Notes, diagnosis and plan, with the nurse's vitals already attached to the visit.
            </x-feature-cell>

            <x-feature-cell title="Prescriptions" icon="phosphor-first-aid-kit"
                class="lg:col-span-3" data-reveal data-reveal-delay="80">
                Autocomplete from your own drug list, at your own prices — not a generic formulary.
            </x-feature-cell>

            <x-feature-cell title="Billing & receipts" icon="phosphor-receipt"
                class="lg:col-span-3" data-reveal>
                Invoices total themselves from the visit. PDF receipts, cash or transfer, nothing missed.
            </x-feature-cell>

            <x-feature-cell title="Audit & oversight" icon="phosphor-chart-line"
                class="lg:col-span-3" data-reveal data-reveal-delay="80">
                Every action attributed to a person, and a dashboard that shows the week at a glance.
            </x-feature-cell>
        </div>
    </x-marketing-section>

    {{-- ========================= YOUR DATA ========================= --}}
    <x-marketing-section tone="warm">
        <div data-reveal>
            <x-marketing-heading
                align="center"
                eyebrow="Your data"
                title="Your clinic's records live in your clinic's own database." />
        </div>

        <x-trust-tiles :tiles="[
            ['icon' => 'phosphor-database', 'name' => 'Per-clinic database', 'sub' => 'Isolated'],
            ['icon' => 'phosphor-lock-key', 'name' => 'Backups', 'sub' => 'Encrypted'],
            ['icon' => 'phosphor-clipboard-text', 'name' => 'Audit trail', 'sub' => 'Every action'],
            ['icon' => 'phosphor-shield-check', 'name' => 'NDPA', 'sub' => 'Aligned'],
            ['icon' => 'phosphor-users-three', 'name' => 'Support', 'sub' => 'Port Harcourt'],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 mt-16">
            <div data-reveal>
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">Isolation</p>
                <h3 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-tight">
                    Separate by construction, not by a filter someone could forget.
                </h3>
                <p class="text-base text-muted mt-4 leading-relaxed">
                    Not a shared table with a column telling clinics apart. Each clinic gets its own
                    database, so one clinic's records cannot be reached from another — and we can say
                    exactly that, in one sentence, to anyone who asks.
                </p>
            </div>

            <div class="flex flex-col gap-8">
                @foreach ([
                    ['icon' => 'phosphor-database', 'title' => 'One database per clinic', 'text' => 'Isolation is structural. There is no query that could return another clinic\'s patients, because their records are not in the same database.'],
                    ['icon' => 'phosphor-lock-key', 'title' => 'Encrypted, tested backups', 'text' => 'Automatic daily backups, AES-256 encrypted, stored off the server — with a restore we have actually rehearsed rather than assumed.'],
                    ['icon' => 'phosphor-shield-check', 'title' => 'Built for the NDPA', 'text' => 'A documented data-isolation guarantee and a per-clinic data-processing agreement, so your compliance paperwork has something to point at.'],
                ] as $i => $item)
                    <div class="flex items-start gap-4" data-reveal data-reveal-delay="{{ $i * 90 }}">
                        <span class="bg-page border border-line rounded-card p-2.5 shrink-0">
                            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-ink" aria-hidden="true" />
                        </span>
                        <div>
                            <h4 class="text-sm font-medium text-ink tracking-tight">{{ $item['title'] }}</h4>
                            <p class="text-sm text-muted mt-1.5 leading-relaxed">{{ $item['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= PRICING ========================= --}}
    <x-marketing-section tone="page">
        <div data-reveal>
            <x-marketing-heading
                align="center"
                eyebrow="Pricing"
                title="Naira pricing, stated plainly."
                lead="No dollar invoices, no surprise conversion. The setup fee is shown up front." />
        </div>

        {{-- Full cards, not the compact variant: the feature checklist is the
             substance of this card, and hiding it was why it read as thin. --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 max-w-5xl mx-auto">
            @foreach ($tiers as $i => $tier)
                <div data-reveal data-reveal-delay="{{ $i * 90 }}">
                    <x-pricing-tier :tier="$tier" />
                </div>
            @endforeach
        </div>

        <p class="text-center mt-10" data-reveal>
            <a href="{{ route('pricing') }}"
                class="group inline-flex items-center gap-1.5 text-sm text-ink font-medium hover:underline">
                Compare every plan
                <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
            </a>
        </p>
    </x-marketing-section>

    {{-- ========================= FAQ ========================= --}}
    <x-marketing-section tone="warm">
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
                 'answer' => 'Yes, once, and it is shown on the pricing page next to each plan. It covers configuring your clinic, loading your drug list and prices, and onboarding your staff.'],
                ['question' => 'Who do I call when something breaks?',
                 'answer' => 'Us, in Port Harcourt. Not an overseas ticket queue. Clinic and Group plans include WhatsApp support.'],
            ]" />
        </div>
    </x-marketing-section>

    {{-- ========================= CLOSING CTA ========================= --}}
    <x-marketing-section tone="page">
        <div data-reveal>
            <x-cta-band />
        </div>
    </x-marketing-section>

@endsection
