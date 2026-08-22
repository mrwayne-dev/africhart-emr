@extends('layouts.marketing')

@section('title', 'Contact — AfriChart')
@section('description', 'Reach AfriChart in Port Harcourt. Email us or send a message, and we reply within one working day.')

@section('content')

    {{--
        Contact keeps the nav and footer, unlike Demo and Sign-Up — it is a
        navigation destination, and someone who arrives here with a question may
        well leave with a link to Pricing instead.

        Composition from the Unikorns contact block
        (landingfolio.com/inspiration/post/unikorns): editorial headline, then
        channels as a labelled column beside the form rather than a row of cards
        above it. Channels sit LEFT so they are read first — the inventory is
        right that for this audience the reachable human matters more than the
        form, even when the channel is email rather than a chat app.
    --}}
    <x-marketing-section tone="page">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            {{-- Channels --}}
            <div class="lg:col-span-5">
                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                    <span class="text-accent">[</span> Contact <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                    data-reveal data-reveal-delay="110">
                    Talk to someone<br class="hidden sm:block"> who built it.
                </h1>

                <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="220">
                    Not a ticket queue and not a chatbot. Your message reaches the people who write
                    the code, in Port Harcourt.
                </p>

                {{-- Email, with copy-to-clipboard. Lifted from the Unikorns
                     reference, and genuinely useful: half the people who read an
                     address on a page want it on their clipboard, not in a new
                     mail client they do not use. --}}
                <div class="border-t border-line mt-10" data-reveal data-reveal-delay="310">
                    <div class="py-6 border-b border-line">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Email us</p>

                        <div class="flex items-center gap-3 flex-wrap"
                            x-data="{
                                copied: false,
                                address: 'hello@africhartemr.com',
                                async copy() {
                                    try {
                                        await navigator.clipboard.writeText(this.address);
                                    } catch {
                                        return; // clipboard blocked — the mailto link still works
                                    }
                                    this.copied = true;
                                    setTimeout(() => (this.copied = false), 2000);
                                },
                            }">
                            <a href="mailto:hello@africhartemr.com"
                                class="text-lg text-ink font-medium hover:underline rounded">hello@africhartemr.com</a>

                            <button type="button" @click="copy()"
                                class="inline-flex items-center gap-1.5 border border-line rounded-full px-3 py-1.5
                                    text-xs text-muted hover:text-ink hover:border-muted/30 transition-colors">
                                <x-phosphor-copy class="w-3.5 h-3.5" aria-hidden="true" />
                                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                            </button>

                            {{-- Announced to screen readers only when it changes,
                                 so the visual chip does not need to be read twice. --}}
                            <span class="sr-only" aria-live="polite" x-text="copied ? 'Address copied to clipboard' : ''"></span>
                        </div>

                        <p class="text-sm text-muted mt-3">We reply within one working day.</p>
                    </div>

                    <div class="py-6 border-b border-line">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Where we are</p>
                        <p class="text-base text-ink-body leading-relaxed">
                            Port Harcourt, Rivers State.
                        </p>
                        <p class="text-sm text-muted mt-2 leading-relaxed">
                            If your clinic is in Port Harcourt we can come to you — say so in your
                            message and we will arrange it.
                        </p>
                    </div>

                    {{-- Route the two higher-intent reasons for landing here to the
                         pages built for them, rather than letting them queue behind
                         a general enquiry. --}}
                    <div class="py-6">
                        <p class="text-xs font-semibold text-muted uppercase tracking-wide mb-3">Looking for something specific?</p>
                        <div class="flex flex-col gap-2.5">
                            <a href="{{ route('demo') }}" class="group inline-flex items-center gap-1.5 text-sm text-ink hover:underline">
                                Book a 15-minute demo
                                <x-phosphor-arrow-right class="w-4 h-4 text-muted transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                            </a>
                            <a href="{{ route('pricing') }}" class="group inline-flex items-center gap-1.5 text-sm text-ink hover:underline">
                                See pricing and what each plan includes
                                <x-phosphor-arrow-right class="w-4 h-4 text-muted transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-7" data-reveal data-reveal-delay="170">
                <div class="bg-page border border-line rounded-card p-6 sm:p-8">
                    <h2 class="text-xl font-medium text-ink tracking-tight">Send us a message</h2>
                    <p class="text-sm text-muted mt-2">Five fields. We read every one of them.</p>

                    <form method="POST" action="{{ route('contact') }}" class="space-y-5 mt-6"
                        x-data="{ loading: false }" @submit="loading = true">
                        @csrf

                        {{-- Honeypot: never shown, never focusable, never announced. --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <x-marketing-field name="contact_name" label="Your name"
                                placeholder="Dr. Emeka Okafor" autocomplete="name" />
                            {{-- Optional: an enquiry may come from someone who does
                                 not run a clinic yet, and demanding one would turn
                                 them away at the first field. --}}
                            <x-marketing-field name="clinic_name" label="Clinic name" optional
                                placeholder="Grace Medical Centre" autocomplete="organization" />
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <x-marketing-field name="email" label="Email" type="email"
                                placeholder="you@clinic.com" autocomplete="email" />
                            <x-marketing-field name="phone" label="Phone" type="tel"
                                placeholder="0803 123 4567" autocomplete="tel" inputmode="tel" />
                        </div>

                        <x-marketing-field name="message" label="How can we help" rows="5"
                            placeholder="What you are trying to work out, and anything we should know about your clinic." />

                        <x-submit-button loadingText="Sending…"
                            class="w-full bg-ink text-white rounded-full px-6 py-3.5 text-sm font-medium hover:bg-ink/90">
                            Send message
                        </x-submit-button>

                        <p class="text-xs text-muted text-center leading-relaxed">
                            We use these details to reply to you. Nothing else.
                            See our <a href="{{ route('legal.privacy') }}" class="text-ink hover:underline">privacy policy</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </x-marketing-section>

@endsection
