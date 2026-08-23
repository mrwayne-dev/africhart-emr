@extends('layouts.focus')

@section('title', 'Get started — AfriChart')
@section('description', 'Start your clinic on AfriChart. Thirty days free, no card required, and we set it up for you.')

@php
    // Labels for the plan carried over from /pricing. The controller has
    // already validated the slug, so this only ever reads one of the three.
    $planLabels = [
        'starter' => ['name' => 'Starter', 'price' => '₦25,000', 'note' => 'Up to 2 doctors'],
        'clinic'  => ['name' => 'Clinic',  'price' => '₦50,000', 'note' => 'Up to 8 doctors'],
        'group'   => ['name' => 'Group',   'price' => '₦40,000', 'note' => 'Per location'],
    ];
@endphp

@section('content')

    {{-- Focused layout: no nav menu, no footer. See layouts/focus. --}}

    {{-- ========================= HERO + FORM ========================= --}}
    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            <div class="lg:col-span-5">
                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                    <span class="text-accent">[</span> Get started <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                    data-reveal data-reveal-delay="110">
                    Start your clinic<br class="hidden sm:block"> on AfriChart.
                </h1>

                <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="220">
                    Thirty days free, no card. We set the clinic up for you — we do not hand you an
                    empty system and wish you luck.
                </p>

                {{-- Reassurance row. Four claims, each true and each checkable. --}}
                <div class="border-t border-line mt-10">
                    @foreach ([
                        ['phosphor-calendar-check', '30 days free, no card required'],
                        ['phosphor-users-three', 'We set it up and train your staff'],
                        ['phosphor-database', 'Your data on its own database'],
                        ['phosphor-arrow-u-up-left', 'Cancel any time — your records stay yours'],
                    ] as $i => [$icon, $text])
                        <div class="flex items-center gap-3 py-4 border-b border-line"
                            data-reveal data-reveal-delay="{{ 340 + $i * 100 }}">
                            <x-dynamic-component :component="$icon" class="w-5 h-5 text-ink shrink-0" aria-hidden="true" />
                            <span class="text-sm text-muted">{{ $text }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="text-sm text-muted mt-8" data-reveal data-reveal-delay="740">
                    Setup is a one-time fee, shown next to each plan on
                    <a href="{{ route('pricing') }}" class="text-ink font-medium hover:underline">pricing</a> —
                    it covers configuring your clinic, loading your drug list and prices, and
                    onboarding your staff. Want to see it first?
                    <a href="{{ route('demo') }}" class="text-ink font-medium hover:underline">Book a demo</a>.
                </p>
            </div>

            <div class="lg:col-span-7" data-reveal data-reveal-delay="170">
                <div class="bg-page border border-line rounded-card p-6 sm:p-8">

                    {{-- Chosen plan, carried from /pricing. Shown above the form
                         rather than re-asked: the visitor made this decision one
                         page ago and a second picker invites them to reconsider. --}}
                    @if ($plan)
                        <div class="flex items-center justify-between gap-4 bg-warm rounded-card px-4 py-3.5 mb-6">
                            <div class="flex items-center gap-3 min-w-0">
                                <x-phosphor-check-circle class="w-5 h-5 text-ink shrink-0" aria-hidden="true" />
                                <p class="text-sm text-ink-body truncate">
                                    <span class="font-medium">You're starting on: {{ $planLabels[$plan]['name'] }}</span>
                                    <span class="text-muted">
                                        — {{ $planLabels[$plan]['price'] }}/month · {{ $planLabels[$plan]['note'] }}
                                    </span>
                                </p>
                            </div>
                            <a href="{{ route('pricing') }}"
                                class="text-sm text-muted hover:text-ink transition-colors shrink-0 rounded">Change</a>
                        </div>
                    @endif

                    <x-form-steps
                        :action="route('signup')"
                        submitLabel="Request access"
                        loadingText="Sending…"
                        :steps="[
                            ['label' => 'About you', 'fields' => ['contact_name', 'email', 'phone']],
                            ['label' => 'About your clinic', 'fields' => ['clinic_name', 'city', 'doctors']],
                            ['label' => 'Confirm', 'fields' => ['plan', 'terms', 'message']],
                        ]">

                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        {{-- STEP 1 — about you --}}
                        <div x-ref="panel1" x-show="step === 1" class="space-y-5">
                            <x-marketing-field name="contact_name" label="Your name"
                                placeholder="Dr. Emeka Okafor" autocomplete="name" />

                            <div class="grid sm:grid-cols-2 gap-5">
                                <x-marketing-field name="email" label="Email" type="email"
                                    placeholder="you@clinic.com" autocomplete="email"
                                    hint="Where we send your login details." />
                                <x-marketing-field name="phone" label="Phone" type="tel"
                                    placeholder="0803 123 4567" autocomplete="tel" inputmode="tel"
                                    pattern="[0-9+\-\s()]{7,}" />
                            </div>
                        </div>

                        {{-- STEP 2 — about the clinic.
                             Clinic name becomes the subdomain, so it gets the live
                             preview. Preview only: there is no clinic registry
                             until the tenancy work lands, so it makes no
                             availability claim. --}}
                        <div x-ref="panel2" x-show="step === 2" class="space-y-5">
                            <div x-data="{
                                clinic: @js(old('clinic_name', $clinic) ?? ''),
                                get slug() {
                                    return this.clinic
                                        .toLowerCase()
                                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                                        .replace(/[^a-z0-9]+/g, '-')
                                        .replace(/^-+|-+$/g, '')
                                        .slice(0, 40)
                                },
                            }">
                                <x-marketing-field name="clinic_name" label="Clinic name"
                                    :value="$clinic" placeholder="Grace Medical Centre"
                                    autocomplete="organization" x-model="clinic" />

                                <p class="mt-2 text-xs text-muted" aria-live="polite">
                                    Your clinic's address will be
                                    <span class="font-mono text-ink-body" x-text="(slug || 'yourclinic') + '.africhartemr.com'">yourclinic.africhartemr.com</span>
                                </p>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <x-marketing-field name="city" label="City"
                                    placeholder="Port Harcourt" autocomplete="address-level2"
                                    hint="We set clinics up in person, so we need to know where you are." />
                                <x-marketing-field name="doctors" label="How many doctors" optional
                                    type="number" placeholder="3" inputmode="numeric" />
                            </div>
                        </div>

                        {{-- STEP 3 — confirm --}}
                        <div x-ref="panel3" x-show="step === 3" class="space-y-5">
                            @if ($plan)
                                <input type="hidden" name="plan" value="{{ $plan }}">
                            @else
                                {{-- Arrived directly rather than from /pricing, so the
                                     plan is asked for here instead of assumed. --}}
                                <x-marketing-field name="plan" label="Which plan" optional
                                    placeholder="Not sure yet — help me choose"
                                    :options="[
                                        'starter' => 'Starter — ₦25,000/month, up to 2 doctors',
                                        'clinic' => 'Clinic — ₦50,000/month, up to 8 doctors',
                                        'group' => 'Group — ₦40,000/month per location',
                                    ]"
                                    hint="You can change this before your trial ends." />
                            @endif

                            <x-marketing-field name="message" label="Anything we should know" optional
                                rows="3" placeholder="Number of staff, what you use today, when you want to start." />

                            {{-- Consent immediately above the submit button, so it is
                                 read before the decision rather than after it. --}}
                            <div>
                                <label for="terms" class="flex items-start gap-3 cursor-pointer">
                                    <input id="terms" name="terms" type="checkbox" value="1" required
                                        @checked(old('terms'))
                                        class="mt-0.5 w-4 h-4 shrink-0 rounded border-line text-ink focus:ring-0 focus:ring-offset-0 accent-ink">
                                    <span class="text-sm text-muted leading-relaxed">
                                        I have read the
                                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener"
                                            class="text-ink hover:underline">privacy policy</a>
                                        and agree to the
                                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener"
                                            class="text-ink hover:underline">terms of service</a>,
                                        including the
                                        <a href="{{ route('legal.dpa') }}" target="_blank" rel="noopener"
                                            class="text-ink hover:underline">data processing agreement</a>
                                        that applies to patient data.
                                    </span>
                                </label>

                                @error('terms')
                                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </x-form-steps>

                    <p class="text-xs text-muted text-center mt-5">
                        {{-- No route('login') here: login lives on the clinic's OWN subdomain, so a
                     central page cannot generate that URL without knowing which clinic —
                     and guessing would send someone to the wrong one. T2.2 "find your
                     clinic" becomes this link once the registry lookup exists. --}}
                        Already set up? Sign in at your clinic's own address —
                        <span class="font-mono text-ink-body">yourclinic.{{ config('tenancy.root_domain') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= WHAT HAPPENS AFTER ========================= --}}
    {{-- Provisioning is operator-driven, not instant. Saying so plainly, with
         the timing attached, makes that read as deliberate care rather than a
         system that is slow. --}}
    <x-marketing-section tone="warm">
        <x-process-strip
            eyebrow="What happens after you sign up"
            title="Your clinic is running by tomorrow."
            lead="We set it up by hand, on purpose — it is the difference between software you bought and software your staff actually use."
            :steps="[
                ['label' => 'We set up your clinic', 'duration' => 'Usually within a day',
                 'body' => 'We create your clinic at its own address, on its own database, and email you the link.',
                 'points' => ['Your name, logo and invoice prefix', 'Your consultation fee', 'Your drug list, at your prices']],
                ['label' => 'You bring your team in', 'duration' => 'An afternoon',
                 'body' => 'An account for each staff member, scoped to the work they do, and we walk them through their first morning.',
                 'points' => ['Reception, nurse, doctor, administrator', 'Training on your own workflow']],
                ['label' => 'Your 30 days start', 'duration' => 'No card required',
                 'body' => 'The trial begins when your clinic goes live, not when you fill in this form.',
                 'points' => ['Setup fee refunded if your staff are not using it daily by day 30']],
            ]" />
    </x-marketing-section>

@endsection
