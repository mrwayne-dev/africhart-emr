@extends('layouts.marketing')

@section('title', 'Features — AfriChart')
@section('description', 'Patient records, live queue, consultations and vitals, prescriptions, billing and receipts, and a full audit trail — the workflow a Nigerian private clinic runs every day.')

@section('content')

    {{-- ========================= INTRO ========================= --}}
    {{-- Not a full-viewport hero: that treatment is Home's signature, and
         repeating it here would dilute it. A compact opener instead. --}}
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

    {{-- ========================= THE SIX ========================= --}}
    {{-- Tone alternates and the visual side flips on every row, so six features
         read as a rhythm instead of six identical slabs. The two that sell
         hardest — the queue and billing — run tall. --}}

    <x-marketing-section tone="warm">
        <x-feature-row id="patient-records" :index="1" title="Patient records" visual="records"
            :points="[
                'One record per patient, with the full visit timeline attached',
                'Age, blood group and allergies surfaced before the doctor opens anything',
                'Archived rather than deleted — medical history is never destroyed',
            ]">
            Every visit, diagnosis and prescription sits on one timeline. Nobody has to ask the
            patient what happened last time, and nothing depends on finding the right paper file.
        </x-feature-row>
    </x-marketing-section>

    <x-marketing-section tone="page" size="tall">
        <x-feature-row id="live-queue" :index="2" title="Live queue" visual="queue" flip
            :points="[
                'Updates on its own — no refreshing, no walking to the front desk to ask',
                'The nurse records vitals while the patient is still waiting',
                'Waiting time visible per patient, so nobody is quietly forgotten',
            ]">
            Who is waiting, who is being seen, and for how long. The board is the same for
            reception, the nurse and the doctor, so the three of them stop interrupting each other
            to find out where a patient is.
        </x-feature-row>
    </x-marketing-section>

    <x-marketing-section tone="warm">
        <x-feature-row id="consultations" :index="3" title="Consultations & vitals" visual="consult"
            :points="[
                'Vitals taken at check-in flow into the consultation automatically',
                'Complaint, examination, diagnosis and plan in one structured note',
                'A doctor can only complete their own consultations',
            ]">
            The nurse takes vitals while the patient waits; when the doctor opens the consultation
            they are already there. No re-entry, and no doctor opening a consultation early just to
            record a temperature.
        </x-feature-row>
    </x-marketing-section>

    <x-marketing-section tone="page">
        <x-feature-row id="prescriptions" :index="4" title="Prescriptions" visual="prescriptions" flip
            :points="[
                'Autocomplete from your own drug list, not a generic formulary',
                'Your prices, which the invoice then uses without re-typing',
                'Dosage, frequency, route and duration captured as structured data',
            ]">
            Prescribe from the catalogue your clinic actually stocks, at the prices your clinic
            actually charges. The invoice picks the prices up by itself, which is where most of the
            leaked revenue in a paper clinic goes missing.
        </x-feature-row>
    </x-marketing-section>

    <x-marketing-section tone="warm" size="tall">
        <x-feature-row id="billing" :index="5" title="Billing & receipts" visual="billing"
            :points="[
                'Invoices total themselves from the consultation and prescription',
                'Cash, transfer or card recorded against the invoice',
                'PDF receipts, and CSV export for whoever does your books',
            ]">
            The invoice is built from what actually happened in the visit — consultation fee plus
            the drugs prescribed, priced from your catalogue. Reception confirms and takes payment
            rather than reconstructing the bill from memory.
        </x-feature-row>
    </x-marketing-section>

    <x-marketing-section tone="page">
        <x-feature-row id="audit" :index="6" title="Audit & oversight" visual="audit" flip
            :points="[
                'Every action attributed to a named person, with a timestamp',
                'A dashboard showing the week: patients seen, consultations, money collected',
                'Owner visibility without standing in the clinic',
            ]">
            Every change is recorded against the person who made it. For an owner who is not in the
            building every day, this is the difference between trusting the numbers and hoping
            they are right.
        </x-feature-row>
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
    <x-marketing-section tone="page" size="tight">
        <div data-reveal>
            <x-cta-band />
        </div>
    </x-marketing-section>

@endsection
