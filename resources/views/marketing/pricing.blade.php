@extends('layouts.marketing')

@section('title', 'Pricing — AfriChart')
@section('description', 'Naira pricing for Nigerian private clinics. Three plans, the setup fee stated up front, and a 30-day trial. Your data is never deleted, even if you stop paying.')

@section('content')

    {{-- ========================= HERO =========================
         Deliberately NOT full-viewport, unlike Features. On a pricing page the
         prices are the content; pushing them a full screen down to make room
         for a headline works against the one job this page has. Tall enough to
         breathe, short enough that the cards are already in view.
    --}}
    <x-marketing-section tone="page">
        <div>
            <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                <span class="text-accent">[</span> Pricing <span class="text-accent">]</span>
            </p>

            <h1 class="text-4xl sm:text-6xl font-medium text-ink tracking-tight leading-[1.03]" data-reveal data-reveal-delay="80">
                Naira pricing, stated plainly.
            </h1>

            <p class="text-lg text-muted mt-6 leading-relaxed max-w-2xl" data-reveal data-reveal-delay="160">
                No dollar invoices, no surprise conversion, and the one-time setup fee shown next to
                every plan rather than mentioned after you commit.
            </p>

            <ul class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-8">
                @foreach (['30-day trial', 'No card to start', 'Cancel anytime'] as $i => $point)
                    <li class="inline-flex items-center gap-1.5 text-sm text-muted"
                        data-reveal data-reveal-delay="{{ 260 + $i * 70 }}">
                        <x-phosphor-check-circle class="w-4 h-4 text-ink" aria-hidden="true" />
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- All three, side by side. Home shows one and hides the rest behind a
             toggle because it is a teaser; here the comparison IS the page. --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-16">
            @foreach ($tiers as $i => $tier)
                <div data-reveal data-reveal-delay="{{ 480 + $i * 90 }}">
                    <x-pricing-tier :tier="$tier" />
                </div>
            @endforeach
        </div>

        <p class="text-sm text-muted mt-8" data-reveal>
            Group is priced per site. Two locations on Group is
            <span class="text-ink">&#8358;80,000</span> a month.
        </p>
    </x-marketing-section>

    {{-- ========================= SETUP FEE ========================= --}}
    {{-- The spec asks for the setup fee stated honestly. Burying it would be the
         easy move; explaining what it buys is the one that survives the sales
         conversation. --}}
    <x-marketing-section tone="warm" size="tight">
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
            <div class="lg:col-span-5" data-reveal>
                <h2 class="text-2xl sm:text-3xl font-medium text-ink tracking-tight leading-tight">
                    What the setup fee is actually for
                </h2>
                <p class="text-base text-muted mt-4 leading-relaxed">
                    It is charged once, before the trial starts. It is not a deposit and it is not
                    a subscription in disguise.
                </p>
            </div>

            <div class="lg:col-span-7 border-t border-line">
                @foreach ([
                    ['We configure your clinic', 'Name, consultation fee, ID prefix and branding, set up for you rather than left as homework.'],
                    ['We load your drug catalogue', 'Your medications at your prices, so invoices are right from the first patient.'],
                    ['We onboard your staff', 'Accounts for each role, and a walkthrough of their first working day.'],
                ] as $i => [$title, $body])
                    <div class="py-5 border-b border-line" data-reveal data-reveal-delay="{{ $i * 70 }}">
                        <h3 class="text-sm font-medium text-ink">{{ $title }}</h3>
                        <p class="text-sm text-muted mt-1.5 leading-relaxed">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= COMPARISON ========================= --}}
    <x-marketing-section tone="page">
        <div data-reveal>
            <x-marketing-heading
                eyebrow="Compare"
                title="Every plan, side by side."
                lead="The audit log and owner dashboard are the line between Starter and Clinic — they are what turns records into oversight." />
        </div>

        <div data-reveal>
            <x-pricing-comparison :groups="$comparison" :tiers="$tiers" />
        </div>
    </x-marketing-section>

    {{-- ========================= FAQ ========================= --}}
    <x-marketing-section tone="warm">
        <div class="max-w-3xl mx-auto" data-reveal>
            <x-marketing-heading align="center" eyebrow="FAQ" title="Before you commit" />

            <x-faq-accordion :items="[
                ['question' => 'What happens if I stop paying?',
                 'answer' => 'Your clinic goes read-only. Everyone can still open and read every record — nothing is hidden and nothing is deleted — but no new patients, consultations or invoices can be written until the subscription is settled. Medical records are not leverage.'],
                ['question' => 'Can I change plans later?',
                 'answer' => 'Yes, in either direction, and the setup fee is not charged again. Moving from Starter to Clinic switches on the audit log and dashboard for the history you have already recorded — nothing needs re-entering.'],
                ['question' => 'Is the setup fee refundable?',
                 'answer' => 'If your staff are not using AfriChart daily by the end of the 30-day trial, we refund the setup fee in full. The condition is real usage, not the calendar.'],
                ['question' => 'Is my data really separate from other clinics?',
                 'answer' => 'Yes. Each clinic runs on its own database, so there is no query that could return records belonging to another clinic. That separation is structural rather than a setting someone could misconfigure.'],
                ['question' => 'How do I pay?',
                 'answer' => 'In naira, by bank transfer or card. We arrange it with you when your clinic is provisioned — there is no card required to start the trial.'],
                ['question' => 'Do I need my own server?',
                 'answer' => 'No. AfriChart runs on our servers and your staff reach it in a browser at your clinic\'s own address. There is nothing to install, no machine in the back office to maintain, and no IT person to hire — a laptop or phone with a connection is the whole requirement.'],
                ['question' => 'What kind of support do I get?',
                 'answer' => 'Email, from Port Harcourt, answered by the people who build AfriChart rather than an overseas ticket queue. Starter includes email support; Clinic and Group get priority handling, and Group adds onboarding.'],
                ['question' => 'What if I have more than one location?',
                 'answer' => 'That is the Group plan, priced per site. Each site keeps its own database and staff, and you get a consolidated dashboard across all of them. Talk to us and we will work out the arrangement.'],
            ]" />
        </div>
    </x-marketing-section>

    {{-- ========================= CLOSING CTA ========================= --}}
    <x-marketing-section tone="page">
        <x-cta-band />
    </x-marketing-section>

@endsection
