@extends('layouts.marketing')

@section('title', 'AfriChart — Clinic management software for Nigerian clinics')
@section('description', 'See every patient, every prescription and every naira in your clinic — from anywhere. Built in Port Harcourt for Nigerian private clinics.')

@section('content')

    {{-- ========================= HERO ========================= --}}
    {{-- Split hero from the customer.io reference: copy left, product visual
         right, dual CTA, trust micro-strip underneath. --}}
    <section class="bg-page pt-14 pb-16 sm:pt-20 sm:pb-24">
        <div class="max-w-5xl mx-auto px-6 sm:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-10 items-center">

                <div>
                    <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-5">
                        For Nigerian private clinics
                    </p>

                    <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.1]">
                        See every patient, every prescription, and every naira.
                    </h1>

                    <p class="text-base text-muted mt-6 leading-relaxed max-w-xl">
                        AfriChart runs your front desk, consulting room and billing in one place —
                        so the queue moves, nothing goes unbilled, and you can check on the clinic
                        from anywhere.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 mt-8">
                        <a href="{{ route('signup') }}"
                            class="bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors text-center">
                            Start free trial
                        </a>
                        <a href="{{ route('demo') }}"
                            class="border border-line text-ink rounded-full px-6 py-3 text-sm font-medium hover:bg-warm transition-colors text-center">
                            Book a demo
                        </a>
                    </div>

                    {{-- Trust micro-strip, straight from the reference. --}}
                    <ul class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-7">
                        @foreach (['30-day free trial', 'Naira pricing', 'Your own database'] as $point)
                            <li class="inline-flex items-center gap-1.5 text-xs text-muted">
                                <x-phosphor-check-circle class="w-4 h-4 text-ink" aria-hidden="true" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Product visual. Built from real markup rather than a screenshot
                     so it stays truthful and sharp at every size. --}}
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
    </section>

    {{-- ========================= HOW IT WORKS ========================= --}}
    <x-marketing-section tone="warm">
        <x-marketing-heading
            eyebrow="How it works"
            title="One flow, from the front desk to the owner's phone."
            lead="Every role sees exactly what they need, and each step hands cleanly to the next." />

        <ol class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ([
                ['icon' => 'phosphor-users-three', 'role' => 'Reception', 'text' => 'Registers the patient and checks them into today\'s queue.'],
                ['icon' => 'phosphor-heartbeat', 'role' => 'Nurse', 'text' => 'Records vitals while the patient waits — before the doctor opens anything.'],
                ['icon' => 'phosphor-stethoscope', 'role' => 'Doctor', 'text' => 'Consults, diagnoses and prescribes, with the vitals already there.'],
                ['icon' => 'phosphor-receipt', 'role' => 'Reception', 'text' => 'Invoice totals itself from the visit. Take payment, print the receipt.'],
            ] as $i => $step)
                <li class="bg-page border border-line rounded-card p-6">
                    <div class="flex items-center justify-between mb-5">
                        <x-dynamic-component :component="$step['icon']" class="w-6 h-6 text-ink" aria-hidden="true" />
                        <span class="text-xs font-semibold text-muted">0{{ $i + 1 }}</span>
                    </div>
                    <h3 class="text-base font-medium text-ink tracking-tight">{{ $step['role'] }}</h3>
                    <p class="text-sm text-muted mt-2 leading-relaxed">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>

        <p class="text-sm text-muted mt-8 max-w-2xl leading-relaxed">
            And the owner sees all of it — every consultation, every invoice, every change —
            without standing in the clinic.
        </p>
    </x-marketing-section>

    {{-- ========================= FEATURE GRID ========================= --}}
    {{-- Bordered grid, not a gapped one: hairlines between cells, per the
         customer.io "journey" reference. The outer ring plus divide-* gives
         clean interior rules without doubling borders. --}}
    <x-marketing-section tone="page">
        <x-marketing-heading
            eyebrow="What you get"
            title="Everything a clinic actually does, in one system."
            lead="Not a hospital suite bolted down to fit. The workflow a private clinic runs every day." />

        <div class="border border-line rounded-card overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 divide-line
                sm:[&>*]:border-b sm:[&>*]:border-line sm:[&>*:nth-child(n+4)]:border-b-0
                sm:[&>*:not(:nth-child(2n+1))]:border-l lg:[&>*]:border-l-0
                lg:[&>*:not(:nth-child(3n+1))]:border-l">
                <x-feature-cell title="Patient records" icon="phosphor-clipboard-text">
                    One record per patient with a full visit timeline — never re-ask what happened last time.
                </x-feature-cell>
                <x-feature-cell title="Live queue" icon="phosphor-clock">
                    Who is waiting, who is being seen, and for how long. Updates on its own.
                </x-feature-cell>
                <x-feature-cell title="Consultations & vitals" icon="phosphor-stethoscope">
                    Notes, diagnosis and plan, with the nurse's vitals already attached.
                </x-feature-cell>
                <x-feature-cell title="Prescriptions" icon="phosphor-first-aid-kit">
                    Autocomplete from your own drug list, with your own prices.
                </x-feature-cell>
                <x-feature-cell title="Billing & receipts" icon="phosphor-receipt">
                    Invoices total themselves from the visit. PDF receipts, cash or transfer.
                </x-feature-cell>
                <x-feature-cell title="Audit & oversight" icon="phosphor-chart-line">
                    Every action attributed to a person, and a dashboard that shows the week at a glance.
                </x-feature-cell>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= TRUST / ISOLATION ========================= --}}
    <x-marketing-section tone="warm">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
            <div>
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">Your data</p>
                <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-tight">
                    Your clinic's records live in your clinic's own database.
                </h2>
                <p class="text-base text-muted mt-4 leading-relaxed">
                    Not a shared table with a column telling clinics apart. A separate database per
                    clinic, so one clinic's records cannot be reached from another — and we can say
                    exactly that, in one sentence, to anyone who asks.
                </p>
            </div>

            <div class="flex flex-col gap-6">
                @foreach ([
                    ['icon' => 'phosphor-database', 'title' => 'Isolated by design', 'text' => 'Each clinic gets its own database. Isolation is structural, not a filter someone could forget to apply.'],
                    ['icon' => 'phosphor-lock-key', 'title' => 'Encrypted backups', 'text' => 'Automatic daily backups, encrypted, stored off the server, with a restore we have actually rehearsed.'],
                    ['icon' => 'phosphor-shield-check', 'title' => 'Built for NDPA', 'text' => 'A documented data-isolation guarantee and a per-clinic data-processing agreement.'],
                ] as $item)
                    <div class="flex items-start gap-4">
                        <span class="bg-page border border-line rounded-card p-2.5 shrink-0">
                            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-ink" aria-hidden="true" />
                        </span>
                        <div>
                            <h3 class="text-sm font-medium text-ink tracking-tight">{{ $item['title'] }}</h3>
                            <p class="text-sm text-muted mt-1.5 leading-relaxed">{{ $item['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= PRICING TEASER ========================= --}}
    <x-marketing-section tone="page">
        <x-marketing-heading
            align="center"
            eyebrow="Pricing"
            title="Naira pricing, stated plainly."
            lead="No dollar invoices, no surprise conversion. Setup fee shown up front." />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ($tiers as $tier)
                <x-pricing-tier :tier="$tier" compact />
            @endforeach
        </div>

        <p class="text-center mt-8">
            <a href="{{ route('pricing') }}"
                class="inline-flex items-center gap-1.5 text-sm text-ink font-medium hover:underline">
                Compare every plan
                <x-phosphor-arrow-right class="w-4 h-4" aria-hidden="true" />
            </a>
        </p>
    </x-marketing-section>

    {{-- ========================= FAQ ========================= --}}
    <x-marketing-section tone="warm">
        <div class="max-w-3xl mx-auto">
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
        <x-cta-band />
    </x-marketing-section>

@endsection
