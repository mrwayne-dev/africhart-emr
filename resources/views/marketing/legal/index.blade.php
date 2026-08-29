@extends('layouts.marketing')

@section('title', 'Legal & company information — AfriChart')
@section('description', 'Who AfriChart Technologies Limited is, our CAC registration and certificate of incorporation, and the three documents that govern a clinic\'s use of the platform.')

@section('content')

    {{-- ========================= HEADER ========================= --}}
    <x-marketing-section tone="page">
        <div class="max-w-3xl">
            <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-6" data-reveal>
                <span class="text-accent">[</span> Legal <span class="text-accent">]</span>
            </p>

            <h1 class="text-4xl sm:text-5xl font-medium text-ink tracking-tight leading-[1.03]"
                data-reveal data-reveal-delay="110">
                Who you are<br class="hidden sm:block"> contracting with.
            </h1>

            <p class="text-lg text-muted mt-6 leading-relaxed" data-reveal data-reveal-delay="220">
                A clinic putting its patient records into someone else's software is entitled to know
                exactly which company is holding them. This page names that company, shows its
                registration, and links the three documents that govern the arrangement.
            </p>
        </div>
    </x-marketing-section>

    {{-- ========================= REGISTERED ENTITY ========================= --}}
    {{--
        The registration details are the substance of this page; the certificate
        image below is corroboration.

        Deliberately in that order. Anyone can put a picture of a certificate on
        a website — the RC number is what makes the claim checkable, because it
        resolves against the CAC's own public register. Leading with the image
        and burying the number would be the weaker arrangement dressed as the
        stronger one.
    --}}
    <x-marketing-section tone="warm">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16">

            <div class="lg:col-span-5">
                <h2 class="text-2xl sm:text-3xl font-medium text-ink tracking-tight" data-reveal>
                    Registered in Nigeria
                </h2>
                <p class="text-muted mt-4 leading-relaxed" data-reveal data-reveal-delay="110">
                    AfriChart is built and operated by a Nigerian company, incorporated with the
                    Corporate Affairs Commission under the Companies and Allied Matters Act 2020.
                </p>
                <p class="text-sm text-muted mt-4 leading-relaxed" data-reveal data-reveal-delay="170">
                    You can verify the registration number independently on the
                    <a href="https://search.cac.gov.ng" target="_blank" rel="noopener"
                        class="text-ink font-medium hover:underline">CAC public search</a>
                    — you do not have to take our word for it, or the certificate's.
                </p>
            </div>

            <div class="lg:col-span-7" data-reveal data-reveal-delay="170">
                <dl class="bg-page border border-line rounded-card divide-y divide-line">
                    @foreach ([
                        ['Registered name', 'AfriChart Technologies Limited'],
                        ['Company type', 'Private company limited by shares'],
                        ['Registration number', 'RC 9782826'],
                        ['Incorporated', '18 August 2026'],
                        ['Incorporated under', 'Companies and Allied Matters Act 2020'],
                        ['Registry', 'Corporate Affairs Commission, Abuja'],
                        ['Principal place of business', 'Port Harcourt, Rivers State, Nigeria'],
                    ] as [$term, $value])
                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-6 px-5 py-4">
                            <dt class="text-sm text-muted sm:w-56 sm:shrink-0">{{ $term }}</dt>
                            <dd class="text-sm text-ink font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                {{--
                    The certificate itself.

                    Shown as the actual issued document — never rebuilt in HTML.
                    A hand-made facsimile of a government certificate is a forgery
                    with good intentions: it would look almost right, carry no
                    provenance, and be trivially copied into someone else's page.

                    ⚠️ The IMAGE is published; the source PDF deliberately is NOT,
                    and must not be dropped into public/ later.

                    The PDF issued by the CAC carries the company's Tax
                    Identification Number; this image does not. A TIN buys a
                    clinic evaluating an EMR precisely nothing, while company
                    impersonation in Nigeria tends to work from exactly this
                    bundle — registered name, RC number, address, TIN. So the
                    trust signal is published and the tax identifier is not.
                    The original PDF lives in storage/app/legal/, outside the web
                    root, and goes out by email if due diligence asks for it.

                    There is no file_exists() fallback here on purpose: a guard
                    that quietly starts serving a PDF the moment one appears in
                    public/documents/ is how the TIN would get published by
                    accident a year from now.
                --}}
                @php $certificateImage = 'images/africhart-certificate-of-incorporation.webp'; @endphp

                @if (file_exists(public_path($certificateImage)))
                    <a href="{{ asset($certificateImage) }}" target="_blank" rel="noopener"
                        class="block group mt-6 rounded-card overflow-hidden border border-line bg-page"
                        data-reveal data-reveal-delay="240">
                        <img src="{{ asset($certificateImage) }}"
                            alt="Certificate of incorporation for AfriChart Technologies Limited, RC 9782826, issued by the Corporate Affairs Commission on 18 August 2026"
                            width="900" height="991" loading="lazy"
                            class="w-full transition-transform duration-500 group-hover:scale-[1.01]">
                    </a>

                    <p class="text-xs text-muted mt-3">
                        Certificate of incorporation, issued by the Corporate Affairs Commission.
                        <span class="text-ink-body">Click to enlarge.</span>
                    </p>
                @endif

            </div>
        </div>
    </x-marketing-section>

    {{-- ========================= THE DOCUMENTS ========================= --}}
    {{--
        The three legal documents had no index anywhere — they were reachable
        only from the footer and the Company dropdown, which is fine for someone
        who already knows they exist and useless for someone looking for them.
    --}}
    <x-marketing-section tone="page">
        <div class="max-w-3xl">
            <h2 class="text-2xl sm:text-3xl font-medium text-ink tracking-tight" data-reveal>
                The documents that govern your account
            </h2>
            <p class="text-muted mt-4 leading-relaxed" data-reveal data-reveal-delay="110">
                All three apply automatically to every clinic. None of them is optional, and none of
                them is written to be difficult.
            </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-5 mt-10">
            @foreach ([
                ['Privacy policy', 'legal.privacy', 'phosphor-lock-key',
                 'What we do with your clinic\'s information, and what we do with the patient records you store.'],
                ['Terms of service', 'legal.terms', 'phosphor-shield-check',
                 'Subscriptions, uptime, who owns the clinical data, and how either side can end the agreement.'],
                ['Data processing agreement', 'legal.dpa', 'phosphor-files',
                 'The NDPA agreement: your clinic is the controller, we are the processor, and this is what we owe you.'],
            ] as $i => [$label, $route, $icon, $blurb])
                <a href="{{ route($route) }}"
                    class="group flex flex-col bg-page border border-line rounded-card p-6 hover:border-ink transition-colors"
                    data-reveal data-reveal-delay="{{ 170 + $i * 90 }}">
                    <x-dynamic-component :component="$icon" class="w-6 h-6 text-ink shrink-0" aria-hidden="true" />
                    <h3 class="text-base font-medium text-ink mt-4">{{ $label }}</h3>
                    <p class="text-sm text-muted mt-2 leading-relaxed flex-1">{{ $blurb }}</p>
                    <span class="inline-flex items-center gap-1.5 text-sm text-ink font-medium mt-4">
                        Read it
                        <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Same notice the three documents carry, repeated here so the index
             cannot imply a level of review the documents themselves disclaim. --}}
        <div class="max-w-3xl mt-10 flex items-start gap-3 border border-line rounded-card p-5"
            data-reveal data-reveal-delay="440">
            <x-phosphor-info class="w-5 h-5 text-muted shrink-0 mt-0.5" aria-hidden="true" />
            <p class="text-sm text-muted leading-relaxed">
                These documents are pending professional legal review. They are written to be accurate
                and readable, and they are not a substitute for advice from your own lawyer.
            </p>
        </div>
    </x-marketing-section>

    {{-- ========================= CONTACT ========================= --}}
    <x-marketing-section tone="warm">
        <div class="max-w-3xl">
            <h2 class="text-2xl sm:text-3xl font-medium text-ink tracking-tight" data-reveal>
                Legal enquiries
            </h2>
            <p class="text-muted mt-4 leading-relaxed" data-reveal data-reveal-delay="110">
                For data-protection requests, contractual questions, or anything on this page, write to
                <a href="mailto:legal@africhartemr.com" class="text-ink font-medium hover:underline">legal@africhartemr.com</a>.
                For everything else, <a href="{{ route('contact') }}" class="text-ink font-medium hover:underline">contact us</a>.
            </p>
        </div>
    </x-marketing-section>

@endsection
