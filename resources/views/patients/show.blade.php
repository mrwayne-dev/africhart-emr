@extends('layouts.app')

@section('title', $patient->full_name . ' — AfriChart EMR')
@section('page-title', 'Patient Record')
@section('page-subtitle', $patient->patient_id)

@section('content')
    <div class="max-w-5xl">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-warm rounded-full flex items-center justify-center text-ink text-lg font-medium shrink-0">
                    {{ strtoupper(substr($patient->full_name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-medium text-ink tracking-tight">{{ $patient->full_name }}</h2>
                    <p class="text-sm text-muted">{{ $patient->patient_id }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 no-print">
                <x-print-button />
                @can('create', \App\Models\Consultation::class)
                    <a href="{{ route('consultations.create', ['patient_id' => $patient->id]) }}"
                        class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-4 py-2 text-sm font-medium hover:bg-warm transition-colors">
                        <x-phosphor-stethoscope class="w-4 h-4" />
                        Start Consultation
                    </a>
                @endcan
                <a href="{{ route('patients.edit', $patient) }}"
                    class="inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors">
                    <x-phosphor-pencil-simple class="w-4 h-4" />
                    Edit
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Details --}}
            <div class="lg:col-span-2">
                <div class="bg-page border border-line rounded-card divide-y divide-line">
                    <x-detail-row label="Full Name" :value="$patient->full_name" />
                    <x-detail-row label="Date of Birth" :value="$patient->date_of_birth->format('j F Y') . ' (' . $patient->age . ' years)'" />
                    <x-detail-row label="Phone Number" :value="$patient->phone" />
                    <x-detail-row label="Blood Group" :value="$patient->blood_group->label()" />
                    <x-detail-row label="Known Allergies" :value="$patient->allergies ?: 'None recorded'" />
                    <x-detail-row label="Registered By" :value="$patient->registeredBy?->name ?? '—'" />
                    <x-detail-row label="Registered On" :value="$patient->created_at->format('j F Y, g:i A')" />
                </div>
            </div>

            {{-- Timeline --}}
            <div class="bg-page border border-line rounded-card p-6">
                <h3 class="text-xs font-semibold text-muted uppercase tracking-wide mb-5">Patient Timeline</h3>
                <x-timeline :events="$timeline" />
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('patients.index') }}" class="text-sm text-muted hover:text-ink transition-colors inline-flex items-center gap-1.5">
                <x-phosphor-arrow-left class="w-4 h-4" />
                Back to patients
            </a>
        </div>
    </div>
@endsection
