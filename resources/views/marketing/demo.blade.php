@extends('layouts.focus')

@section('title', 'Book a demo — AfriChart')
@section('description', 'See AfriChart run a full clinic day in 15 minutes. No slide deck, no obligation — with someone in Port Harcourt.')

@section('content')

    {{--
        Focused layout: no nav menu, no footer. Someone who has reached a form
        has already chosen, and every other link is an invitation to leave it.

        Structure follows the v2 page inventory: hero split with the form above
        the fold, then the three things that remove submission anxiety — what
        happens next, what the demo actually covers, and the objections.
    --}}

    {{-- ========================= HERO + FORM ========================= --}}
    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            <div class="lg:col-span-5">
                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                    <span class="text-accent">[</span> Book a demo <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                    data-reveal data-reveal-delay="110">
                    See a full clinic day<br class="hidden sm:block"> in fifteen minutes.
                </h1>

                <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="220">
                    Not a slide deck. We walk your actual clinic day — check-in, vitals,
                    consultation, invoice — and you decide whether it beats what you do now.
                </p>

                {{-- The time commitment stated before the form, not after it. --}}
                <div class="border-t border-line mt-10">
                    @foreach ([
                        ['phosphor-clock', 'Fifteen minutes, and we keep to it'],
                        ['phosphor-currency-ngn', 'No cost, and no obligation afterwards'],
                        ['phosphor-envelope-simple', 'We reply within one working day'],
                        ['phosphor-users-three', 'Bring whoever runs your front desk'],
                    ] as $i => [$icon, $text])
                        <div class="flex items-center gap-3 py-4 border-b border-line"
                            data-reveal data-reveal-delay="{{ 340 + $i * 100 }}">
                            <x-dynamic-component :component="$icon" class="w-5 h-5 text-ink shrink-0" aria-hidden="true" />
                            <span class="text-sm text-muted">{{ $text }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- The page has no nav, so the two other reasons someone might be
                     here need an explicit way out. --}}
                <p class="text-sm text-muted mt-8" data-reveal data-reveal-delay="740">
                    Already decided?
                    <a href="{{ route('signup') }}" class="text-ink font-medium hover:underline">Get started</a>
                    and skip the call. Just have a question?
                    <a href="{{ route('contact') }}" class="text-ink font-medium hover:underline">Contact us</a>.
                </p>
            </div>

            <div class="lg:col-span-7" data-reveal data-reveal-delay="170">
                <div class="bg-page border border-line rounded-card p-6 sm:p-8">
                    <x-form-steps
                        :action="route('demo')"
                        submitLabel="Request a demo"
                        loadingText="Sending…"
                        :steps="[
                            ['label' => 'About you', 'fields' => ['contact_name', 'phone', 'email']],
                            ['label' => 'About your clinic', 'fields' => ['clinic_name', 'city', 'doctors', 'preferred_time', 'heard_from', 'message']],
                        ]">

                        {{-- Honeypot: never shown, never focusable, never announced.
                             LeadRequest rejects the submission if it is filled. --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        {{-- STEP 1 — who you are.
                             x-ref is what form-steps validates before advancing. --}}
                        <div x-ref="panel1" x-show="step === 1" class="space-y-5">
                            <x-marketing-field name="contact_name" label="Your name"
                                placeholder="Dr. Emeka Okafor" autocomplete="name" />

                            <div class="grid sm:grid-cols-2 gap-5">
                                <x-marketing-field name="phone" label="Phone" type="tel"
                                    placeholder="0803 123 4567" autocomplete="tel" inputmode="tel"
                                    pattern="[0-9+\-\s()]{7,}"
                                    hint="We call this number to arrange the demo." />
                                <x-marketing-field name="email" label="Email" type="email"
                                    placeholder="you@clinic.com" autocomplete="email" />
                            </div>
                        </div>

                        {{-- STEP 2 — about the clinic. --}}
                        <div x-ref="panel2" x-show="step === 2" class="space-y-5">
                            <x-marketing-field name="clinic_name" label="Clinic name"
                                placeholder="Grace Medical Centre" autocomplete="organization" />

                            <div class="grid sm:grid-cols-2 gap-5">
                                <x-marketing-field name="city" label="City" optional
                                    placeholder="Port Harcourt" autocomplete="address-level2" />
                                <x-marketing-field name="doctors" label="How many doctors" optional
                                    type="number" placeholder="3" inputmode="numeric"
                                    hint="Helps us suggest the right plan." />
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <x-marketing-field name="preferred_time" label="Best time to reach you" optional
                                    placeholder="Any time"
                                    :options="[
                                        'morning' => 'Morning (8am – 12pm)',
                                        'afternoon' => 'Afternoon (12pm – 4pm)',
                                        'evening' => 'Evening (4pm – 7pm)',
                                    ]" />
                                <x-marketing-field name="heard_from" label="How did you hear about us" optional
                                    placeholder="Select…"
                                    :options="[
                                        'search' => 'Search',
                                        'referral' => 'Another clinic or colleague',
                                        'social' => 'Social media',
                                        'event' => 'A conference or event',
                                        'other' => 'Somewhere else',
                                    ]" />
                            </div>

                            <x-marketing-field name="message" label="Anything we should know" optional
                                rows="3" placeholder="What you use today, and what is not working about it." />
                        </div>
                    </x-form-steps>

                    <p class="text-xs text-muted text-center leading-relaxed mt-5">
                        We use these details to contact you about AfriChart. Nothing else.
                        See our <a href="{{ route('legal.privacy') }}" class="text-ink hover:underline">privacy policy</a>.
                    </p>
                </div>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= WHAT HAPPENS NEXT ========================= --}}
    <x-marketing-section tone="warm">
        <x-process-strip
            eyebrow="What happens next"
            title="Three steps, and you know where each one ends."
            lead="Nobody should submit a form and wonder what they have just started."
            :steps="[
                ['label' => 'You book', 'duration' => 'Two minutes',
                 'body' => 'The form above. We only ask what we need to run a demo that is actually about your clinic.'],
                ['label' => 'We show you', 'duration' => 'Fifteen minutes',
                 'body' => 'A walkthrough of your own clinic day — on a call, or in person if you are in Port Harcourt.'],
                ['label' => 'You decide', 'duration' => 'No obligation',
                 'body' => 'If it fits, we handle setup and train your staff. If it does not, no hard feelings and no follow-up campaign.'],
            ]" />
    </x-marketing-section>

    {{-- ========================= THE AGENDA ========================= --}}
    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">
            <div class="lg:col-span-5" data-reveal>
                <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">The agenda</p>
                <h2 class="text-3xl sm:text-4xl font-medium text-ink tracking-tight leading-[1.05]">
                    In fifteen minutes you'll see:
                </h2>
                <p class="text-base text-muted mt-5 leading-relaxed">
                    The same four moments that make up your day. We run them in order, on real
                    screens, and you interrupt whenever you want.
                </p>
            </div>

            <div class="lg:col-span-7">
                <div class="border-t border-line">
                    @foreach ([
                        ['The live queue', 'Who is waiting, who is being seen, how long they have been there.'],
                        ['A consultation and a prescription', 'Notes and diagnosis with the nurse\'s vitals already attached, prescribing from your own drug list.'],
                        ['An invoice totalling itself', 'The visit becomes a priced invoice with no arithmetic, and a receipt that prints.'],
                        ['The owner dashboard, on a phone', 'What the clinic did today, from wherever you happen to be.'],
                    ] as $i => [$title, $body])
                        <div class="flex items-start gap-4 py-5 border-b border-line"
                            data-reveal data-reveal-delay="{{ $i * 100 }}">
                            <x-phosphor-check class="w-5 h-5 text-ink shrink-0 mt-0.5" aria-hidden="true" />
                            <div>
                                <h3 class="text-base font-medium text-ink tracking-tight">{{ $title }}</h3>
                                <p class="text-sm text-muted mt-1.5 leading-relaxed">{{ $body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= TRUST ========================= --}}
    <x-marketing-section tone="warm-alt" size="tight">
        <x-trust-strip :items="[
            ['icon' => 'phosphor-database', 'name' => 'Per-clinic database', 'sub' => 'Isolated'],
            ['icon' => 'phosphor-currency-ngn', 'name' => 'Naira pricing', 'sub' => 'Always'],
            ['icon' => 'phosphor-users-three', 'name' => 'Port Harcourt', 'sub' => 'Built and supported'],
            ['icon' => 'phosphor-lock-key', 'name' => 'Backups', 'sub' => 'Encrypted'],
            ['icon' => 'phosphor-shield-check', 'name' => 'NDPA', 'sub' => 'Aligned'],
        ]" />
    </x-marketing-section>

    {{-- ========================= OBJECTIONS ========================= --}}
    <x-marketing-section tone="page">
        <div class="max-w-3xl mx-auto" data-reveal>
            <x-marketing-heading align="center" eyebrow="Before you book" title="The things people ask first" />

            <x-faq-accordion :items="[
                ['question' => 'Do I need to prepare anything?',
                 'answer' => 'No. Turn up and we will drive. If you want to see your own drug list or your own fee in it, send them over beforehand and we will load them — but that is optional, not homework.'],
                ['question' => 'Can you come to my clinic?',
                 'answer' => 'If you are in Port Harcourt, yes, and we would rather. Seeing your actual front desk tells us more in five minutes than a call does in thirty. Anywhere else in Nigeria, we do it on a call.'],
                ['question' => 'Is there any cost to the demo?',
                 'answer' => 'None, and there is no obligation afterwards. We are not going to put you on a mailing list either.'],
                ['question' => 'What if my staff are not technical?',
                 'answer' => 'That is the normal case, and it is who the system was designed for. If your staff can use WhatsApp, they can use AfriChart. Bring your receptionist to the demo — they are usually the one who decides whether it will actually get used.'],
                ['question' => 'How soon can we start after the demo?',
                 'answer' => 'Usually within a day. We configure your clinic, load your drug list and prices, create an account for each staff member, and walk your team through their first morning.'],
            ]" />
        </div>
    </x-marketing-section>

@endsection
