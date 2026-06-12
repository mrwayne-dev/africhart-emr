@extends('layouts.app')

@section('title', 'Consultation '.$consultation->consultation_id.' — AfriChart EMR')
@section('page-title', 'Consultation')
@section('page-subtitle', $consultation->consultation_id)

@php
    $patient = $consultation->patient;
    $isCompleted = $consultation->status === \App\Enums\ConsultationStatus::Completed;
@endphp

@section('content')
<div x-data="livePoll({ url: '{{ route('consultations.live.show', $consultation) }}', interval: 8000, mode: 'notify', hash: '{{ $liveHash }}' })">
    {{-- Live "this was updated elsewhere" banner (notify mode protects unsaved input) --}}
    <div x-show="stale" x-cloak
        class="mb-5 flex items-center justify-between gap-3 rounded-card border border-l-4 border-l-ink border-line bg-warm px-4 py-3">
        <span class="text-sm text-ink-body">This consultation was updated. Refresh to see the latest.</span>
        <button type="button" @click="window.location.reload()"
            class="inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-1.5 text-sm font-medium hover:bg-ink/90 transition-colors shrink-0">
            <x-phosphor-arrow-clockwise class="w-4 h-4" /> Refresh
        </button>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('consultations.index') }}" class="text-muted hover:text-ink">
                <x-phosphor-arrow-left class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-lg font-medium text-ink tracking-tight">{{ $consultation->consultation_id }}</h1>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $consultation->status->color() }}">
                    {{ $consultation->status->label() }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 no-print">
            <x-print-button />
            @can('update', $consultation)
                <a href="{{ route('consultations.edit', $consultation) }}"
                    class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-4 py-2 text-sm font-medium hover:bg-warm transition-colors">
                    <x-phosphor-pencil-simple class="w-4 h-4" /> Edit
                </a>
            @endcan
            @can('complete', $consultation)
                @unless ($isCompleted)
                    <form method="POST" action="{{ route('consultations.complete', $consultation) }}"
                        x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        @method('PATCH')
                        <x-submit-button loadingText="Completing…"
                            class="bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90">
                            Complete Consultation
                        </x-submit-button>
                    </form>
                @endunless
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Patient info --}}
        <div class="bg-page border border-line rounded-card p-6 lg:col-span-2">
            <h2 class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">Patient</h2>
            @if ($patient)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <a href="{{ route('patients.show', $patient) }}" class="text-lg font-medium text-ink hover:underline">{{ $patient->full_name }}</a>
                        <p class="text-sm text-muted mt-0.5">{{ $patient->patient_id }} · {{ $patient->age }} yrs · {{ $patient->blood_group->label() }}</p>
                        @if ($patient->allergies)
                            <p class="text-sm text-accent mt-2">Allergies: {{ $patient->allergies }}</p>
                        @endif
                    </div>
                    @if ($consultation->doctor)
                        <div class="text-sm shrink-0 sm:text-right">
                            <span class="block text-xs text-muted uppercase tracking-wide">Attending</span>
                            <span class="text-ink-body">{{ $consultation->doctor->name }}</span>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-muted">Patient record unavailable.</p>
            @endif
        </div>

        {{-- Vitals --}}
        <div class="bg-page border border-line rounded-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-semibold text-muted uppercase tracking-wide">Vitals</h2>
                @can('recordVitals', $consultation)
                    <button type="button" @click="$dispatch('open-modal', 'vitals')"
                        class="text-sm text-ink font-medium hover:underline">
                        {{ $consultation->has_vitals ? 'Update' : 'Record' }}
                    </button>
                @endcan
            </div>
            @if ($consultation->has_vitals)
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Temp</dt><dd class="text-ink">{{ $consultation->temperature ? $consultation->temperature.' °C' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">BP</dt><dd class="text-ink">{{ $consultation->blood_pressure ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Pulse</dt><dd class="text-ink">{{ $consultation->pulse_rate ? $consultation->pulse_rate.' bpm' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Weight</dt><dd class="text-ink">{{ $consultation->weight ? $consultation->weight.' kg' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Height</dt><dd class="text-ink">{{ $consultation->height ? $consultation->height.' cm' : '—' }}</dd></div>
                    @if ($consultation->bmi)
                        <div class="flex justify-between border-t border-line pt-2 mt-2"><dt class="text-muted">BMI</dt><dd class="text-ink font-medium">{{ $consultation->bmi }}</dd></div>
                    @endif
                </dl>
                @if ($consultation->vitals_notes)
                    <p class="text-sm text-muted mt-3 pt-3 border-t border-line">{{ $consultation->vitals_notes }}</p>
                @endif
            @else
                <p class="text-sm text-muted">No vitals recorded yet.</p>
            @endif
        </div>
    </div>

    {{-- Clinical notes --}}
    <div class="bg-page border border-line rounded-card p-6 mt-5">
        <h2 class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">Clinical Notes</h2>
        <div class="space-y-4 text-sm">
            <div>
                <p class="text-muted mb-1">Chief Complaint</p>
                <p class="text-ink-body whitespace-pre-line">{{ $consultation->chief_complaint }}</p>
            </div>
            <div>
                <p class="text-muted mb-1">Notes</p>
                <p class="text-ink-body whitespace-pre-line">{{ $consultation->clinical_notes }}</p>
            </div>
            <div>
                <p class="text-muted mb-1">Diagnosis</p>
                <p class="text-ink-body whitespace-pre-line">{{ $consultation->diagnosis ?: '—' }}</p>
            </div>
            <div>
                <p class="text-muted mb-1">Plan</p>
                <p class="text-ink-body whitespace-pre-line">{{ $consultation->plan ?: '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Prescriptions --}}
    <div class="bg-page border border-line rounded-card p-6 mt-5">
        <h2 class="text-xs font-semibold text-muted uppercase tracking-wide mb-4">Prescriptions</h2>

        @forelse ($consultation->prescriptions as $prescription)
            <div class="flex items-start justify-between py-3 border-b border-line last:border-0">
                <div class="text-sm">
                    <p class="text-ink font-medium">{{ $prescription->medication_name }} {{ $prescription->dosage }}</p>
                    <p class="text-muted">
                        {{ $prescription->route->label() }} · {{ $prescription->frequency }} · {{ $prescription->duration }}@if ($prescription->quantity) · Qty {{ $prescription->quantity }}@endif
                    </p>
                    @if ($prescription->instructions)
                        <p class="text-muted mt-0.5 italic">{{ $prescription->instructions }}</p>
                    @endif
                </div>
                @can('update', $consultation)
                    <form method="POST" action="{{ route('prescriptions.destroy', $prescription) }}"
                        onsubmit="return confirm('Remove this prescription?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-muted hover:text-accent" title="Remove">
                            <x-phosphor-trash class="w-4 h-4" />
                        </button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="text-sm text-muted">No prescriptions recorded.</p>
        @endforelse

        @can('update', $consultation)
            <div class="mt-5 pt-5 border-t border-line" x-data="prescriptionForm(@js($medications))">
                <form method="POST" action="{{ route('prescriptions.store', $consultation) }}" @submit="loading = true">
                    @csrf

                    @if ($errors->any())
                        <div class="mb-4 text-sm text-accent">Please complete every prescription row.</div>
                    @endif

                    <datalist id="medication-presets">
                        @foreach ($medications as $med)
                            <option value="{{ $med['name'] }}"></option>
                        @endforeach
                    </datalist>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 mb-2 items-start">
                            <input type="text" :name="`items[${index}][medication_name]`" x-model="item.medication_name"
                                @change="applyPreset(index)" list="medication-presets" placeholder="Medication" required
                                class="sm:col-span-3 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                            <input type="text" :name="`items[${index}][dosage]`" x-model="item.dosage" placeholder="Dosage" required
                                class="sm:col-span-2 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                            <input type="text" :name="`items[${index}][frequency]`" x-model="item.frequency" placeholder="Frequency" required
                                class="sm:col-span-2 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                            <input type="text" :name="`items[${index}][duration]`" x-model="item.duration" placeholder="Duration" required
                                class="sm:col-span-2 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                            <select :name="`items[${index}][route]`" x-model="item.route"
                                class="sm:col-span-2 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                                @foreach (\App\Enums\MedicationRoute::cases() as $route)
                                    <option value="{{ $route->value }}">{{ $route->label() }}</option>
                                @endforeach
                            </select>
                            <div class="sm:col-span-1 flex items-center">
                                <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                    class="text-muted hover:text-accent" title="Remove row">
                                    <x-phosphor-x class="w-4 h-4" />
                                </button>
                            </div>
                            <input type="text" :name="`items[${index}][instructions]`" x-model="item.instructions" placeholder="Instructions (optional)"
                                class="sm:col-span-5 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                            <input type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" placeholder="Qty"
                                class="sm:col-span-2 bg-warm rounded text-sm px-3 py-2 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                        </div>
                    </template>

                    <div class="flex items-center justify-between mt-3">
                        <button type="button" @click="addItem()" class="inline-flex items-center gap-1 text-sm text-ink font-medium hover:underline">
                            <x-phosphor-plus class="w-4 h-4" /> Add another
                        </button>
                        <button type="submit" :disabled="loading"
                            class="bg-ink text-white rounded-full px-5 py-2 text-sm font-medium hover:bg-ink/90 disabled:opacity-60">
                            <span x-show="!loading">Save Prescriptions</span>
                            <span x-show="loading" x-cloak>Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        @endcan
    </div>

    {{-- Invoice --}}
    <div class="bg-page border border-line rounded-card p-6 mt-5">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold text-muted uppercase tracking-wide">Invoice</h2>
            @if ($consultation->invoice)
                <a href="{{ route('invoices.show', $consultation->invoice) }}" class="text-sm text-ink font-medium hover:underline">View invoice →</a>
            @endif
        </div>
        @if ($consultation->invoice)
            <p class="text-sm text-muted mt-3">
                {{ $consultation->invoice->invoice_number }} ·
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $consultation->invoice->status->color() }}">{{ $consultation->invoice->status->label() }}</span> ·
                <span class="text-ink font-medium">₦{{ number_format((float) $consultation->invoice->total, 2) }}</span>
            </p>
        @else
            <div class="flex items-center justify-between mt-3">
                <p class="text-sm text-muted">No invoice created yet.</p>
                @can('create', \App\Models\Invoice::class)
                    <form method="POST" action="{{ route('invoices.generate', $consultation) }}"
                        x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <x-submit-button loadingText="Generating…"
                            class="bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90">
                            Generate Invoice
                        </x-submit-button>
                    </form>
                @endcan
            </div>
        @endif
    </div>

    {{-- Vitals modal --}}
    @can('recordVitals', $consultation)
        <x-modal name="vitals" title="Record Vitals">
            @include('consultations.vitals-form')
        </x-modal>
    @endcan
</div>
@endsection
