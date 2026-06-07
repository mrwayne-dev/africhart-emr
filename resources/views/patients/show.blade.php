@extends('layouts.app')

@section('title', $patient->full_name . ' — AfriChart EMR')
@section('page-title', 'Patient Record')
@section('page-subtitle', $patient->patient_id)

@section('content')
    <div class="max-w-3xl">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-warm rounded-full flex items-center justify-center text-ink text-lg font-medium shrink-0">
                    {{ strtoupper(substr($patient->full_name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-medium text-ink tracking-tight">{{ $patient->full_name }}</h2>
                    <p class="text-sm text-muted">{{ $patient->patient_id }}</p>
                </div>
            </div>
            <a href="{{ route('patients.edit', $patient) }}"
                class="inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors">
                <x-phosphor-pencil-simple class="w-4 h-4" />
                Edit
            </a>
        </div>

        {{-- Details --}}
        <div class="bg-page border border-line rounded-card divide-y divide-line">
            <x-detail-row label="Full Name" :value="$patient->full_name" />
            <x-detail-row label="Date of Birth" :value="$patient->date_of_birth->format('j F Y') . ' (' . $patient->age . ' years)'" />
            <x-detail-row label="Phone Number" :value="$patient->phone" />
            <x-detail-row label="Blood Group" :value="$patient->blood_group->label()" />
            <x-detail-row label="Known Allergies" :value="$patient->allergies ?: 'None recorded'" />
            <x-detail-row label="Registered By" :value="$patient->registeredBy?->name ?? '—'" />
            <x-detail-row label="Registered On" :value="$patient->created_at->format('j F Y, g:i A')" />
        </div>

        <div class="mt-6">
            <a href="{{ route('patients.index') }}" class="text-sm text-muted hover:text-ink transition-colors inline-flex items-center gap-1.5">
                <x-phosphor-arrow-left class="w-4 h-4" />
                Back to patients
            </a>
        </div>
    </div>
@endsection
