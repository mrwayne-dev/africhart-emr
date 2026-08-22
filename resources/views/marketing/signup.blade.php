@extends('layouts.marketing')

@section('title', 'Get started — AfriChart')
@section('description', 'Tell us about your clinic and we will set it up. Thirty days free, no card required.')

@section('content')

    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            {{-- What happens next.

                 The Wise reference used a progress stepper, but this is a single
                 form — a stepper implying four screens the visitor never sees
                 would be a lie about the process. This gives the same sense of a
                 guided path while describing what actually happens. --}}
            <div class="lg:col-span-5">
                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                    <span class="text-accent">[</span> Get started <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                    data-reveal data-reveal-delay="80">
                    Thirty days free.<br class="hidden sm:block"> No card.
                </h1>

                <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="160">
                    Tell us about your clinic and we set it up for you — we do not hand you an
                    empty system and wish you luck.
                </p>

                <ol class="mt-10 border-t border-line">
                    @foreach ([
                        ['You tell us about the clinic', 'The form beside this. Two minutes.'],
                        ['We set it up', 'Your name, fee, drug catalogue and prices, and an account for each staff member.'],
                        ['We walk your team through day one', 'On WhatsApp or a call, at a time your clinic is quiet.'],
                        ['You run 30 days free', 'If your staff are not using it daily by day 30, the setup fee is refunded.'],
                    ] as $i => [$title, $body])
                        <li class="flex items-start gap-4 py-5 border-b border-line"
                            data-reveal data-reveal-delay="{{ 240 + $i * 70 }}">
                            <span class="font-mono text-xs text-muted tracking-widest pt-1 shrink-0">
                                0{{ $i + 1 }}
                            </span>
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ $title }}</span>
                                <span class="block text-sm text-muted mt-1 leading-relaxed">{{ $body }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-7" data-reveal data-reveal-delay="120">
                <div class="bg-page border border-line rounded-card p-6 sm:p-8">
                    <form method="POST" action="{{ route('signup') }}" class="space-y-5"
                        x-data="{ loading: false }" @submit="loading = true">
                        @csrf

                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            {{-- Prefilled when the visitor came from Home's address finder. --}}
                            <x-marketing-field name="clinic_name" label="Clinic name"
                                :value="$clinic" placeholder="Grace Medical Centre" autocomplete="organization" />
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
                            rows="3" placeholder="Number of staff, what you use today, when you want to start." />

                        <x-submit-button loadingText="Sending…"
                            class="w-full bg-ink text-white rounded-full px-6 py-3.5 text-sm font-medium hover:bg-ink/90">
                            Request access
                        </x-submit-button>

                        <p class="text-xs text-muted text-center">
                            Already set up?
                            <a href="{{ route('login') }}" class="text-ink hover:underline">Sign in to your clinic</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </x-marketing-section>

@endsection
