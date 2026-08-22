@extends('layouts.focus')

@section('title', 'Book a demo — AfriChart')
@section('description', 'Fifteen minutes on your own clinic workflow, with someone in Port Harcourt. No slide deck.')

@section('content')

    {{--
        Focused layout: no nav menu, no footer, no closing CTA band. Everything
        that is not the form or a reason to complete it has been removed.

        Two columns, reassurance on the left rather than stacked above the form
        — the pattern the Clearbit/TwentyThree/Albacross references all settle
        on, and it keeps the first field above the fold on a laptop.
    --}}
    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            {{-- Pitch --}}
            <div class="lg:col-span-5">
                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                    <span class="text-accent">[</span> Book a demo <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                    data-reveal data-reveal-delay="110">
                    Fifteen minutes,<br class="hidden sm:block"> on your workflow.
                </h1>

                <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="220">
                    Not a slide deck. We walk your actual clinic day — check-in, vitals,
                    consultation, invoice — and you decide whether it beats what you do now.
                </p>

                <div class="border-t border-line mt-10">
                    @foreach ([
                        ['phosphor-clock', 'We reply within one working day'],
                        ['phosphor-envelope-simple', 'By email first, then a call at a time that suits you'],
                        ['phosphor-users-three', 'Bring whoever runs your front desk'],
                        ['phosphor-currency-ngn', 'No cost, and no obligation afterwards'],
                    ] as $i => [$icon, $text])
                        <div class="flex items-center gap-3 py-4 border-b border-line"
                            data-reveal data-reveal-delay="{{ 340 + $i * 100 }}">
                            <x-dynamic-component :component="$icon" class="w-5 h-5 text-ink shrink-0" aria-hidden="true" />
                            <span class="text-sm text-muted">{{ $text }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Already decided? Say so, rather than making them find /pricing
                     from a page that deliberately has no nav. --}}
                <p class="text-sm text-muted mt-8" data-reveal data-reveal-delay="730">
                    Already know you want it?
                    <a href="{{ route('signup') }}" class="text-ink font-medium hover:underline">Get started instead</a>
                    and skip the call.
                </p>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-7" data-reveal data-reveal-delay="170">
                <div class="bg-page border border-line rounded-card p-6 sm:p-8">
                    <form method="POST" action="{{ route('demo') }}" class="space-y-5"
                        x-data="{ loading: false }" @submit="loading = true">
                        @csrf

                        {{-- Honeypot: never shown, never focusable, never announced.
                             LeadRequest rejects the submission if it is filled. --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <x-marketing-field name="clinic_name" label="Clinic name"
                                placeholder="Grace Medical Centre" autocomplete="organization" />
                            <x-marketing-field name="contact_name" label="Your name"
                                placeholder="Dr. Emeka Okafor" autocomplete="name" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <x-marketing-field name="email" label="Email" type="email"
                                placeholder="you@clinic.com" autocomplete="email" />
                            <x-marketing-field name="phone" label="Phone" type="tel"
                                placeholder="0803 123 4567" autocomplete="tel" inputmode="tel" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <x-marketing-field name="city" label="City" optional
                                placeholder="Port Harcourt" autocomplete="address-level2" />
                            <x-marketing-field name="doctors" label="How many doctors" optional
                                type="number" placeholder="3" inputmode="numeric" />
                        </div>

                        {{-- Qualifying fields. In the form, not a follow-up call:
                             the references are unanimous that asking here costs
                             one dropdown and saves an exchange of emails. --}}
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
                            rows="4" placeholder="What you use today, and what is not working about it." />

                        <x-submit-button loadingText="Sending…"
                            class="w-full bg-ink text-white rounded-full px-6 py-3.5 text-sm font-medium hover:bg-ink/90">
                            Request a demo
                        </x-submit-button>

                        <p class="text-xs text-muted text-center leading-relaxed">
                            We use these details to contact you about AfriChart. Nothing else.
                            See our <a href="{{ route('legal.privacy') }}" class="text-ink hover:underline">privacy policy</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </x-marketing-section>

@endsection
