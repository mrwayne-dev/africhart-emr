@extends('layouts.marketing')

@section('title', 'Features — AfriChart')
@section('description', 'Patient records, live queue, consultations and vitals, prescriptions, billing and receipts, and a full audit trail — the workflow a Nigerian private clinic runs every day.')

@section('content')

    {{-- ========================= HERO =========================
         Full viewport and LIGHT on purpose. Home owns the dark image treatment;
         repeating it here would flatten the contrast between the two pages.

         Composition adapted from the surge and Spendflo references — centred
         editorial heading, then the page's own contents as pills, then a row of
         facts — rendered in our tokens rather than theirs.

         min-h is calc(100svh-4rem) because this page's nav is solid and sits in
         normal flow above the hero, unlike Home where it overlays.

         The pills do real work: each is an anchor into the section it names, so
         the hero previews the page AND navigates it. scroll-smooth is already on
         <html> in the marketing layout, so they glide rather than jump.
    --}}
    <section class="relative min-h-[calc(100svh-4rem)] flex flex-col bg-page">

        <div class="flex-1 flex items-center">
            <div class="max-w-7xl mx-auto px-6 sm:px-8 w-full py-16 sm:py-20">

                <div>
                    <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                        <span class="text-accent">[</span> Features <span class="text-accent">]</span>
                    </p>

                    <h1 class="text-4xl sm:text-6xl lg:text-7xl font-medium text-ink tracking-tight leading-[1.02]"
                        data-reveal data-reveal-delay="110">
                        The whole visit,<br class="hidden sm:block"> end to end.
                    </h1>

                    <p class="text-lg text-muted mt-7 leading-relaxed max-w-2xl" data-reveal data-reveal-delay="220">
                        Six things a private clinic does every day. Each one hands cleanly to the
                        next, so nobody re-types what someone else already recorded — and the owner
                        can see all of it without standing in the building.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 mt-9" data-reveal data-reveal-delay="340">
                        <a href="{{ route('signup') }}"
                            class="group inline-flex items-center justify-center gap-2 bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors">
                            Get started
                            <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                        </a>
                        <a href="{{ route('demo') }}"
                            class="inline-flex items-center justify-center border border-line text-ink rounded-full px-6 py-3 text-sm font-medium hover:bg-warm hover:border-muted/30 transition-colors">
                            Book a demo
                        </a>
                    </div>
                </div>

                {{-- The six, as jump links --}}
                <nav aria-label="Jump to a feature" class="flex flex-wrap gap-2 mt-14">
                    @foreach ([
                        ['#patient-records', 'Patient records'],
                        ['#live-queue', 'Live queue'],
                        ['#consultations', 'Consultations & vitals'],
                        ['#prescriptions', 'Prescriptions'],
                        ['#billing', 'Billing & receipts'],
                        ['#audit', 'Audit & oversight'],
                        ['#roles', 'Roles & permissions'],
                        ['#reporting', 'Reporting & exports'],
                    ] as $i => [$href, $label])
                        <a href="{{ $href }}"
                            data-reveal data-reveal-delay="{{ 480 + $i * 80 }}"
                            class="group inline-flex items-center gap-1.5 border border-line rounded-full pl-4 pr-3 py-2
                                text-sm text-muted transition-colors hover:bg-warm hover:text-ink hover:border-muted/30">
                            {{ $label }}
                            <x-phosphor-arrow-down class="w-3.5 h-3.5 opacity-50 transition-transform duration-300 group-hover:translate-y-0.5" aria-hidden="true" />
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        {{-- Facts, pinned to the foot of the viewport. True and checkable — no
             clinic counts or uptime figures, because there are none yet. --}}
        <div class="border-t border-line">
            <div class="max-w-7xl mx-auto px-6 sm:px-8">
                <dl class="grid grid-cols-2 sm:grid-cols-4">
                    @foreach ([
                        ['4', 'Staff roles, each scoped to its own work'],
                        ['5', 'Steps from front desk to owner'],
                        ['1', 'Database per clinic, never shared'],
                        ['30', 'Days free, no card required'],
                    ] as $i => [$value, $label])
                        <div class="py-7 sm:py-9 sm:px-8 sm:first:pl-0 sm:last:pr-0
                                {{ $i % 2 ? 'pl-6' : 'pr-6' }} sm:border-l sm:border-line sm:first:border-l-0"
                            data-reveal data-reveal-delay="{{ $i * 100 }}">
                            {{-- Final value is in the HTML, so with no JS the real
                                 figure is already on screen; countUp only takes over
                                 once Alpine is running. --}}
                            <dt class="text-4xl sm:text-5xl font-medium text-ink tracking-tight tabular-nums"
                                x-data="countUp({{ $value }})" x-text="display">{{ $value }}</dt>
                            <dd class="text-sm text-muted mt-2 leading-snug">{{ $label }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- ========================= THE EIGHT =========================
         Three different architectures, deliberately mixed: split · panel ·
         split · duo · panel · split · duo · split. Eight identical rows is the
         exact failure diagnosed on Home's original grid, and layout variation
         solves it more convincingly than alternating a background colour. --}}

    <x-marketing-section tone="warm">
        <x-feature-split id="patient-records" :index="1" title="Patient records" visual="records"
            :items="[
                ['icon' => 'phosphor-clipboard-text', 'title' => 'One timeline per patient',
                 'body' => 'Every visit, diagnosis and prescription in order, on one screen.'],
                ['icon' => 'phosphor-heartbeat', 'title' => 'Allergies surfaced first',
                 'body' => 'Blood group and known allergies sit at the top, before anyone prescribes.'],
                ['icon' => 'phosphor-arrow-counter-clockwise', 'title' => 'Archived, never deleted',
                 'body' => 'Removing a patient hides them from the list and keeps the history intact.'],
            ]">
            Nobody has to ask the patient what happened last time, and nothing depends on finding
            the right paper file in the right drawer.
        </x-feature-split>
    </x-marketing-section>

    <x-marketing-section tone="page">
        <x-feature-panel id="live-queue" :index="2" title="Live queue" visual="queue"
            :notes="[
                ['icon' => 'phosphor-clock', 'body' => 'Updates on its own. No refreshing, and no walking to the front desk to ask where a patient is.'],
                ['icon' => 'phosphor-heartbeat', 'body' => 'The nurse records vitals while the patient is still waiting, so the doctor opens a consultation that is already complete.'],
            ]">
            One board for reception, the nurse and the doctor — so the three of them stop
            interrupting each other to find out who is where.
        </x-feature-panel>
    </x-marketing-section>

    <x-marketing-section tone="warm">
        <x-feature-split id="consultations" :index="3" title="Consultations &amp; vitals" visual="consult" flip
            :items="[
                ['icon' => 'phosphor-heartbeat', 'title' => 'Vitals arrive with the patient',
                 'body' => 'Taken at check-in and pulled into the consultation automatically.'],
                ['icon' => 'phosphor-stethoscope', 'title' => 'Structured, not freeform',
                 'body' => 'Complaint, examination, diagnosis and plan as separate fields.'],
                ['icon' => 'phosphor-shield-check', 'title' => 'Scoped to the treating doctor',
                 'body' => 'A doctor can complete their own consultations, and only their own.'],
            ]">
            The nurse takes vitals while the patient waits; by the time the doctor opens the
            consultation they are already there. No re-entry, and no doctor opening a consultation
            early just to record a temperature.
        </x-feature-split>
    </x-marketing-section>

    <x-marketing-section tone="page">
        <x-feature-duo id="prescriptions" :index="4" title="Prescriptions"
            :cards="[
                ['visual' => 'prescriptions', 'icon' => 'phosphor-first-aid-kit', 'title' => 'Prescribe in seconds',
                 'body' => 'Autocomplete from your own drug list. Dosage, frequency, route and duration captured as structured data, not a note.'],
                ['visual' => 'catalogue', 'icon' => 'phosphor-receipt', 'title' => 'Priced from your catalogue',
                 'body' => 'The invoice picks up your prices by itself — which is where most of the leaked revenue in a paper clinic goes missing.'],
            ]">
            Prescribe from the catalogue your clinic actually stocks, at the prices your clinic
            actually charges.
        </x-feature-duo>
    </x-marketing-section>

    <x-marketing-section tone="warm">
        <x-feature-panel id="billing" :index="5" title="Billing &amp; receipts" visual="billing" flip
            :notes="[
                ['icon' => 'phosphor-receipt', 'body' => 'Cash, transfer or card recorded against the invoice, with a PDF receipt for the patient.'],
                ['icon' => 'phosphor-download-simple', 'body' => 'CSV export for whoever does your books, so month-end is not a reconstruction exercise.'],
            ]">
            The invoice is built from what actually happened in the visit — consultation fee plus
            the drugs prescribed. Reception confirms and takes payment rather than rebuilding the
            bill from memory.
        </x-feature-panel>
    </x-marketing-section>

    <x-marketing-section tone="page">
        <x-feature-split id="audit" :index="6" title="Audit &amp; oversight" visual="audit"
            :items="[
                ['icon' => 'phosphor-clipboard-text', 'title' => 'Every action attributed',
                 'body' => 'Who did what, and when — recorded automatically, not by anyone remembering to.'],
                ['icon' => 'phosphor-chart-line', 'title' => 'The week at a glance',
                 'body' => 'Patients seen, consultations completed, money collected.'],
                ['icon' => 'phosphor-users-three', 'title' => 'Oversight from anywhere',
                 'body' => 'For an owner who is not in the building every day.'],
            ]">
            For an owner who is not there every day, this is the difference between trusting the
            numbers and hoping they are right.
        </x-feature-split>
    </x-marketing-section>

    {{-- ========================= 07 · ROLES ========================= --}}
    <x-marketing-section tone="warm">
        <x-feature-duo id="roles" :index="7" title="Roles &amp; permissions"
            :cards="[
                ['visual' => 'roles', 'icon' => 'phosphor-users-three', 'title' => 'Four roles, four views',
                 'body' => 'Receptionist, nurse, doctor and administrator. Each signs in to the work they actually do, not a menu of everything with most of it greyed out.'],
                ['visual' => 'audit', 'icon' => 'phosphor-lock-key', 'title' => 'Enforced on the server',
                 'body' => 'Permissions are checked on every request, not hidden in the interface — a nurse cannot reach billing by guessing a URL, and every attempt is logged.'],
            ]">
            Who can see what is a clinical and a financial question, so it is decided once and
            enforced everywhere.
        </x-feature-duo>
    </x-marketing-section>

    {{-- ========================= 08 · REPORTING ========================= --}}
    <x-marketing-section tone="page">
        <x-feature-split id="reporting" :index="8" title="Reporting &amp; exports" visual="exports" flip
            :items="[
                ['icon' => 'phosphor-chart-line', 'title' => 'The month, without adding it up',
                 'body' => 'Patients seen, consultations completed, revenue collected and what is still outstanding — calculated from the records as they are entered.'],
                ['icon' => 'phosphor-download-simple', 'title' => 'Yours to take away',
                 'body' => 'Export to CSV for your accountant, or print a clean PDF. Your data is never locked inside the system.'],
                ['icon' => 'phosphor-plugs-connected', 'title' => 'A REST API when you need it',
                 'body' => 'Token-authenticated endpoints for patients, consultations, prescriptions, invoices and the queue, with interactive documentation. Group plan.'],
            ]">
            Numbers a clinic owner can act on, and a way to get them out of AfriChart and into
            whatever else you use.
        </x-feature-split>
    </x-marketing-section>

    {{-- ========================= CLOSING CTA ========================= --}}
    <x-marketing-section tone="page">
        <x-cta-band />
    </x-marketing-section>

@endsection
