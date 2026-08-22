@extends('layouts.marketing')

@section('title', 'Book a demo — AfriChart')
@section('description', 'Fifteen minutes on your own clinic workflow, with someone in Port Harcourt. No slide deck.')

@section('content')

    {{-- Compact hero, like Pricing: on a page whose job is a form, pushing the
         form a full screen down works against it. --}}
    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            {{-- Pitch --}}
            <div class="lg:col-span-5">
                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                    <span class="text-accent">[</span> Book a demo <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                    data-reveal data-reveal-delay="80">
                    Fifteen minutes,<br class="hidden sm:block"> on your workflow.
                </h1>

                <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="160">
                    Not a slide deck. We walk your actual clinic day — check-in, vitals,
                    consultation, invoice — and you decide whether it beats what you do now.
                </p>

                <div class="border-t border-line mt-10">
                    @foreach ([
                        ['phosphor-clock', 'We reply within one working day'],
                        ['phosphor-whatsapp-logo', 'On WhatsApp or a call, whichever suits you'],
                        ['phosphor-users-three', 'Bring whoever runs your front desk'],
                    ] as $i => [$icon, $text])
                        <div class="flex items-center gap-3 py-4 border-b border-line"
                            data-reveal data-reveal-delay="{{ 240 + $i * 70 }}">
                            <x-dynamic-component :component="$icon" class="w-5 h-5 text-ink shrink-0" aria-hidden="true" />
                            <span class="text-sm text-muted">{{ $text }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-7" data-reveal data-reveal-delay="120">
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
                            <x-marketing-field name="phone" label="Phone (WhatsApp)" type="tel"
                                placeholder="0803 123 4567" autocomplete="tel" inputmode="tel" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <x-marketing-field name="city" label="City" optional
                                placeholder="Port Harcourt" autocomplete="address-level2" />
                            <x-marketing-field name="doctors" label="How many doctors" optional
                                type="number" placeholder="3" inputmode="numeric" />
                        </div>

                        <x-marketing-field name="message" label="Anything we should know" optional
                            rows="4" placeholder="What you use today, and what is not working about it." />

                        <x-submit-button loadingText="Sending…"
                            class="w-full bg-ink text-white rounded-full px-6 py-3.5 text-sm font-medium hover:bg-ink/90">
                            Request a demo
                        </x-submit-button>

                        <p class="text-xs text-muted text-center">
                            We use these details to contact you about AfriChart. Nothing else.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </x-marketing-section>

    <x-marketing-section tone="warm">
        <x-cta-band />
    </x-marketing-section>

@endsection
