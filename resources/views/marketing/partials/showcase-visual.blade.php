{{--
    Showcase visuals — one per feature, selected by $key.

    Built from real markup rather than screenshots so they stay sharp at any
    size and never drift out of date with the product. Decorative: the copy
    beside them carries the meaning, so the whole block is aria-hidden.
--}}
<div class="bg-page border border-line rounded-card p-5 sm:p-6" aria-hidden="true">
    @switch($key)

        @case('records')
            <p class="text-sm font-medium text-ink tracking-tight">Chioma A. Nwosu</p>
            <p class="font-mono text-xs text-muted mt-1">ACH-20260821-0026</p>
            <div class="flex flex-col gap-3 mt-5 border-t border-line pt-5">
                @foreach ([
                    ['18 Aug', 'Malaria', 'Artemether/Lumefantrine'],
                    ['02 Jul', 'Tension headache', 'Paracetamol 500mg'],
                    ['14 May', 'Routine BP review', 'Amlodipine 5mg'],
                ] as [$date, $dx, $rx])
                    <div class="flex items-start gap-4">
                        <span class="font-mono text-xs text-muted shrink-0 w-14 pt-0.5">{{ $date }}</span>
                        <div class="min-w-0">
                            <p class="text-sm text-ink truncate">{{ $dx }}</p>
                            <p class="text-xs text-muted mt-0.5 truncate">{{ $rx }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            @break

        @case('queue')
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm font-medium text-ink tracking-tight">Today's queue</p>
                <span class="inline-flex items-center gap-1.5 text-xs text-muted">
                    {{-- Static dot. The pulsing version animated forever, on
                         default easing, inside an aria-hidden decorative mock —
                         the "Live" label beside it already carries the meaning,
                         and an infinite animation costs CPU on low-end phones
                         for nothing. This is the one thing removed this pass. --}}
                    <span class="inline-flex rounded-full h-2 w-2 bg-ink"></span>
                    Live
                </span>
            </div>
            <div class="flex flex-col gap-2.5">
                @foreach ([
                    ['Chioma A. Nwosu', 'Fever · 08:42', 'In consultation'],
                    ['Emeka C. Obi', 'Follow-up · 08:55', 'Waiting'],
                    ['Fatima B. Mohammed', 'New complaint · 09:10', 'Waiting'],
                    ['Oluwaseun D. Adeyemi', 'Lab results · 09:24', 'Waiting'],
                ] as [$name, $meta, $status])
                    <div class="bg-warm border border-line rounded-card px-4 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ $name }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $meta }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shrink-0
                            {{ $status === 'In consultation' ? 'bg-ink text-white' : 'bg-page text-muted' }}">
                            {{ $status }}
                        </span>
                    </div>
                @endforeach
            </div>
            @break

        @case('consult')
            <p class="text-sm font-medium text-ink tracking-tight">Consultation</p>
            <p class="font-mono text-xs text-muted mt-1">ACH-C-20260821-0002</p>
            <div class="grid grid-cols-3 gap-3 mt-5">
                @foreach ([['Temp', '37.2°C'], ['BP', '124/82'], ['Pulse', '78']] as [$label, $value])
                    <div class="bg-warm border border-line rounded-card px-3 py-2.5">
                        <p class="text-xs text-muted">{{ $label }}</p>
                        <p class="text-sm font-medium text-ink mt-0.5 tabular-nums">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 pt-5 border-t border-line">
                <p class="text-xs text-muted">Diagnosis</p>
                <p class="text-sm text-ink mt-1">Malaria</p>
                <p class="text-xs text-muted mt-4">Prescription</p>
                <p class="text-sm text-ink mt-1">Artemether/Lumefantrine 20/120mg · 2× daily · 3 days</p>
            </div>
            @break

        @case('prescriptions')
            <p class="text-sm font-medium text-ink tracking-tight">Prescription</p>
            <p class="text-xs text-muted mt-1">From your own drug list, at your own prices</p>
            <div class="flex flex-col gap-2.5 mt-5">
                @foreach ([
                    ['Artemether/Lumefantrine', '20/120mg · 2x daily · 3 days', '1,500'],
                    ['Paracetamol', '500mg · 3x daily · 5 days', '200'],
                    ['Omeprazole', '20mg · once daily · 14 days', '900'],
                ] as [$drug, $dose, $price])
                    <div class="bg-warm border border-line rounded-card px-4 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ $drug }}</p>
                            <p class="text-xs text-muted mt-0.5 truncate">{{ $dose }}</p>
                        </div>
                        <span class="text-sm text-ink tabular-nums shrink-0">&#8358;{{ $price }}</span>
                    </div>
                @endforeach
            </div>
            @break

        @case('catalogue')
            <p class="text-sm font-medium text-ink tracking-tight">Drug catalogue</p>
            <p class="text-xs text-muted mt-1">Your list, your prices</p>
            <div class="mt-5 divide-y divide-line border-t border-line">
                @foreach ([
                    ['Paracetamol', '500mg, 1000mg', '200'],
                    ['Amoxicillin', '250mg, 500mg', '800'],
                    ['Metformin', '500mg, 850mg', '600'],
                    ['Amlodipine', '5mg, 10mg', '700'],
                ] as [$name, $doses, $price])
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm text-ink truncate">{{ $name }}</p>
                            <p class="text-xs text-muted mt-0.5 truncate">{{ $doses }}</p>
                        </div>
                        <span class="text-sm text-ink tabular-nums shrink-0">&#8358;{{ $price }}</span>
                    </div>
                @endforeach
            </div>
            @break

        @case('billing')
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-ink tracking-tight">Invoice</p>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-ink text-white">Paid</span>
            </div>
            <p class="font-mono text-xs text-muted mt-1">ACH-INV-20260821-0001</p>
            <div class="flex flex-col gap-2.5 mt-5">
                @foreach ([['Consultation', '5,000'], ['Artemether/Lumefantrine ×6', '1,500'], ['Paracetamol ×15', '200']] as [$item, $amount])
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-muted truncate">{{ $item }}</span>
                        <span class="text-sm text-ink tabular-nums shrink-0">&#8358;{{ $amount }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-between mt-5 pt-4 border-t border-line">
                <span class="text-sm font-medium text-ink">Total</span>
                <span class="text-lg font-medium text-ink tracking-tight tabular-nums">&#8358;6,700</span>
            </div>
            @break

        @case('audit')
            <p class="text-sm font-medium text-ink tracking-tight">Activity</p>
            <div class="flex flex-col gap-4 mt-5">
                @foreach ([
                    ['09:24', 'Front Desk — Chioma', 'Marked invoice ACH-INV-…-0001 as paid'],
                    ['09:02', 'Dr. Emeka Okafor', 'Completed consultation ACH-C-…-0002'],
                    ['08:47', 'Nurse Amina', 'Recorded vitals for Emeka C. Obi'],
                    ['08:31', 'Front Desk — Chioma', 'Registered patient Fatima B. Mohammed'],
                ] as [$time, $who, $what])
                    <div class="flex items-start gap-4">
                        <span class="font-mono text-xs text-muted shrink-0 w-11 pt-0.5">{{ $time }}</span>
                        <div class="min-w-0">
                            <p class="text-sm text-ink truncate">{{ $who }}</p>
                            <p class="text-xs text-muted mt-0.5 truncate">{{ $what }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            @break

        @case('roles')
            <p class="text-sm font-medium text-ink tracking-tight">Permissions</p>
            <div class="mt-5 border-t border-line">
                @foreach ([
                    ['Receptionist', 'Register · Queue · Invoice', 2],
                    ['Nurse', 'Queue · Vitals', 1],
                    ['Doctor', 'Consult · Prescribe · Lab', 3],
                    ['Administrator', 'Everything, plus the audit log', 4],
                ] as [$role, $scope, $filled])
                    <div class="flex items-center justify-between gap-4 py-3 border-b border-line">
                        <div class="min-w-0">
                            <p class="text-sm text-ink truncate">{{ $role }}</p>
                            <p class="text-xs text-muted mt-0.5 truncate">{{ $scope }}</p>
                        </div>
                        {{-- Four pips, filled to the role's reach. A visual scale of
                             access, not a claim about a specific permission count. --}}
                        <div class="flex items-center gap-1 shrink-0" aria-hidden="true">
                            @for ($p = 1; $p <= 4; $p++)
                                <span @class([
                                    'w-1.5 h-1.5 rounded-full',
                                    'bg-ink' => $p <= $filled,
                                    'bg-line' => $p > $filled,
                                ])></span>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
            @break

        @case('exports')
            <p class="text-sm font-medium text-ink tracking-tight">This month</p>
            <div class="grid grid-cols-2 gap-px bg-line mt-5 rounded overflow-hidden">
                @foreach ([
                    ['Patients seen', '412'],
                    ['Consultations', '389'],
                    ['Collected', '&#8358;1.84m'],
                    ['Outstanding', '&#8358;236k'],
                ] as [$label, $value])
                    <div class="bg-page p-4">
                        <p class="text-xs text-muted">{{ $label }}</p>
                        <p class="text-xl font-medium text-ink tracking-tight tabular-nums mt-1">{!! $value !!}</p>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-2 mt-5">
                @foreach (['CSV', 'PDF', 'Print'] as $format)
                    <span class="border border-line rounded-full px-3 py-1 text-xs text-muted">{{ $format }}</span>
                @endforeach
            </div>
            @break

    @endswitch
</div>
