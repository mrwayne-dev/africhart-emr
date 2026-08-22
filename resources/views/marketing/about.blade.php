@extends('layouts.marketing')

@section('title', 'About — AfriChart')
@section('description', 'AfriChart is built in Port Harcourt for Nigerian private clinics, by the people who answer the phone when something breaks.')

@section('content')

    {{-- ========================= HERO =========================
         Full viewport like Features, but a different composition — a single
         editorial statement rather than pills and figures. Repeating the same
         hero shape on every page is what makes a site feel templated.
    --}}
    <section class="relative min-h-[calc(100svh-4rem)] flex flex-col bg-page">
        <div class="flex-1 flex items-center">
            <div class="max-w-7xl mx-auto px-6 sm:px-8 w-full py-16 sm:py-20">

                <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-8" data-reveal>
                    <span class="text-accent">[</span> About <span class="text-accent">]</span>
                </p>

                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-medium text-ink tracking-tight leading-[1.02] max-w-5xl"
                    data-reveal data-reveal-delay="80">
                    Built in Port Harcourt,<br class="hidden sm:block"> for clinics like yours.
                </h1>

                <p class="text-lg text-muted mt-8 leading-relaxed max-w-2xl" data-reveal data-reveal-delay="160">
                    Most clinic software sold in Nigeria was designed somewhere else, priced in
                    dollars, and supported from a timezone that is asleep when your front desk is
                    busiest. AfriChart was not.
                </p>
            </div>
        </div>

        <div class="border-t border-line">
            <div class="max-w-7xl mx-auto px-6 sm:px-8">
                <dl class="grid grid-cols-2 sm:grid-cols-4">
                    @foreach ([
                        ['Port Harcourt', 'Where it is built and supported'],
                        ['Naira', 'How it is priced, always'],
                        ['4', 'Clinic roles it was designed around'],
                        ['1', 'Database per clinic, never shared'],
                    ] as $i => [$value, $label])
                        <div class="py-7 sm:py-9 sm:px-8 sm:first:pl-0 sm:last:pr-0
                                {{ $i % 2 ? 'pl-6' : 'pr-6' }} sm:border-l sm:border-line sm:first:border-l-0"
                            data-reveal data-reveal-delay="{{ 240 + $i * 70 }}">
                            <dt @class([
                                'font-medium text-ink tracking-tight',
                                'text-4xl sm:text-5xl tabular-nums' => is_numeric($value),
                                'text-2xl sm:text-3xl' => ! is_numeric($value),
                            ])>{{ $value }}</dt>
                            <dd class="text-sm text-muted mt-2 leading-snug">{{ $label }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- ========================= THE STORY ========================= --}}
    <x-marketing-section tone="warm">
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-16">
            <div class="lg:col-span-4" data-reveal>
                <p class="text-xs font-semibold text-muted uppercase tracking-wide">Why it exists</p>
            </div>

            <div class="lg:col-span-8 max-w-2xl">
                <div class="text-lg text-ink-body leading-relaxed space-y-6" data-reveal data-reveal-delay="80">
                    <p>
                        AfriChart started as one clinic's system. Not a product looking for a market
                        — a real front desk, a real queue, and a real owner who could not tell you
                        how much the clinic had collected that day without asking three people.
                    </p>
                    <p>
                        Building it inside an actual clinic changed what got built. Vitals move to
                        check-in because that is when the nurse takes them. Invoices total
                        themselves because reception was re-typing drug prices from memory. The
                        audit log exists because an owner who is not in the building every day has
                        no other way to know what happened.
                    </p>
                    <p>
                        None of that comes from a requirements document. It comes from watching a
                        clinic run and noticing where the paper was faster.
                    </p>
                </div>

                <blockquote class="border-l-2 border-ink pl-6 mt-12" data-reveal data-reveal-delay="160">
                    <p class="text-xl sm:text-2xl font-medium text-ink tracking-tight leading-snug">
                        The goal was never to digitise a clinic. It was to make the digital version
                        faster than the paper one — because otherwise the paper wins.
                    </p>
                </blockquote>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= WHAT WE HOLD TO ========================= --}}
    <x-marketing-section tone="page">
        <div data-reveal>
            <x-marketing-heading
                eyebrow="What we hold to"
                title="Four things we will not trade away."
                lead="Not values on a wall — decisions already made in the code, that cost us something." />
        </div>

        <div class="border-t border-line">
            @foreach ([
                ['Your records are never leverage',
                 'Stop paying and the clinic goes read-only. Everyone can still open and read every record. Nothing is deleted, nothing is hidden, and we will not hold a patient history hostage to an invoice.'],
                ['Your data is separate by construction',
                 'Each clinic gets its own database rather than a shared table with a column telling clinics apart. It is more work to run and it is the only version we can defend in one sentence.'],
                ['Priced in naira, stated up front',
                 'Including the setup fee, which is on the pricing page rather than in the contract. No dollar invoices and no conversion surprise at the end of the month.'],
                ['Support that answers',
                 'From Port Harcourt, in your working hours. Not an overseas ticket queue that replies once you have already sent the patient home.'],
            ] as $i => [$title, $body])
                <div class="grid sm:grid-cols-12 gap-3 sm:gap-10 py-8 border-b border-line
                        transition-colors hover:bg-warm/60 -mx-4 px-4"
                    data-reveal data-reveal-delay="{{ $i * 70 }}">
                    <span class="sm:col-span-1 font-mono text-xs text-muted tracking-widest pt-1.5">
                        [ 0{{ $i + 1 }} ]
                    </span>
                    <h3 class="sm:col-span-4 text-xl font-medium text-ink tracking-tight">{{ $title }}</h3>
                    <p class="sm:col-span-7 text-base text-muted leading-relaxed">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </x-marketing-section>

    {{-- ========================= WHO BUILDS IT ========================= --}}
    <x-marketing-section tone="warm" size="tight">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div data-reveal>
                <h2 class="text-2xl sm:text-3xl font-medium text-ink tracking-tight leading-tight">
                    Who actually builds it
                </h2>
                <p class="text-base text-muted mt-4 leading-relaxed">
                    AfriChart is developed by <span class="text-ink">Lymora Tech</span> in Port
                    Harcourt, Rivers State. Small team, one product, and the same people who write
                    the code answer the messages when something is wrong.
                </p>
            </div>

            <div class="flex flex-col gap-5" data-reveal data-reveal-delay="80">
                @foreach ([
                    ['icon' => 'phosphor-whatsapp-logo', 'title' => 'WhatsApp, not a ticket portal',
                     'body' => 'Because that is where Nigerian clinics already work.'],
                    ['icon' => 'phosphor-users-three', 'title' => 'We set your clinic up ourselves',
                     'body' => 'Configuration, drug catalogue and staff onboarding are done with you, not left as homework.'],
                ] as $item)
                    <div class="flex items-start gap-4 bg-page border border-line rounded-card p-5
                            transition-all duration-200 hover:-translate-y-0.5 hover:border-muted/30">
                        <span class="bg-warm border border-line rounded-card p-2.5 shrink-0">
                            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-ink" aria-hidden="true" />
                        </span>
                        <div>
                            <h3 class="text-sm font-medium text-ink tracking-tight">{{ $item['title'] }}</h3>
                            <p class="text-sm text-muted mt-1.5 leading-relaxed">{{ $item['body'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= CLOSING CTA ========================= --}}
    <x-marketing-section tone="page">
        <x-cta-band />
    </x-marketing-section>

@endsection
