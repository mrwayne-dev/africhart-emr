@extends('layouts.marketing')

@section('title', 'Features — AfriChart')
@section('description', 'Patient records, live queue, consultations and vitals, prescriptions, billing and receipts, and a full audit trail — the workflow a Nigerian private clinic runs every day.')

@section('content')

    {{-- ========================= INTRO ========================= --}}
    {{-- Compact opener, not a second full-viewport hero: that treatment is
         Home's signature and repeating it would dilute it. --}}
    <x-marketing-section tone="page">
        <div class="max-w-3xl">
            <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                <span class="text-accent">[</span> Features <span class="text-accent">]</span>
            </p>

            <h1 class="text-4xl sm:text-6xl font-medium text-ink tracking-tight leading-[1.03]" data-reveal data-reveal-delay="80">
                The whole visit, end to end.
            </h1>

            <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="160">
                Six things a private clinic does every day. Each one hands cleanly to the next,
                so nobody re-types what someone else already recorded — and the owner can see
                all of it without standing in the building.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 mt-9" data-reveal data-reveal-delay="240">
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
    </x-marketing-section>

    {{-- ========================= THE SIX =========================
         Three different architectures, deliberately mixed: split · panel ·
         split · duo · panel · split. Six identical rows is the exact failure
         diagnosed on Home's original grid, and layout variation solves it more
         convincingly than alternating a background colour. --}}

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

    {{-- ========================= ROLES + API ========================= --}}
    <x-marketing-section tone="warm-alt" size="tight">
        <div class="grid md:grid-cols-2 gap-10 lg:gap-16">
            <div data-reveal>
                <h2 class="text-2xl font-medium text-ink tracking-tight">Four roles, each seeing only their work</h2>
                <p class="text-base text-muted mt-3 leading-relaxed">
                    Receptionist, nurse, doctor and administrator. Permissions are enforced on the
                    server, not just hidden in the interface — a nurse cannot reach billing by
                    guessing a URL.
                </p>
            </div>
            <div data-reveal data-reveal-delay="80">
                <h2 class="text-2xl font-medium text-ink tracking-tight">A REST API when you need it</h2>
                <p class="text-base text-muted mt-3 leading-relaxed">
                    Token-authenticated endpoints for patients, consultations, prescriptions,
                    invoices and the queue, with interactive documentation. Available on the Group
                    plan for clinics that want to integrate.
                </p>
            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= CLOSING CTA ========================= --}}
    <x-marketing-section tone="page">
        <x-cta-band />
    </x-marketing-section>

@endsection
