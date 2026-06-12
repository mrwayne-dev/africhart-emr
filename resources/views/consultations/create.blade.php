@extends('layouts.app')

@section('title', 'New Consultation — AfriChart EMR')
@section('page-title', 'New Consultation')
@section('page-subtitle', 'Start a clinical visit')

@section('content')
    <a href="{{ route('consultations.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink mb-6">
        <x-phosphor-arrow-left class="w-4 h-4" /> Back to consultations
    </a>

    <div class="bg-page border border-line rounded-card p-6 max-w-2xl">
        <form method="POST" action="{{ route('consultations.store') }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <div>
                <label for="patient_id" class="block text-sm font-medium text-ink-body mb-2">Patient</label>
                <select name="patient_id" id="patient_id" required
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    <option value="">Select a patient…</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}" @selected(old('patient_id', $selectedPatientId) == $patient->id)>
                            {{ $patient->full_name }} ({{ $patient->patient_id }})
                        </option>
                    @endforeach
                </select>
                @error('patient_id')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="chief_complaint" class="block text-sm font-medium text-ink-body mb-2">Chief Complaint</label>
                <textarea name="chief_complaint" id="chief_complaint" rows="2" required
                    placeholder="Why the patient came in…"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('chief_complaint') }}</textarea>
                @error('chief_complaint')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="clinical_notes" class="block text-sm font-medium text-ink-body mb-2">Clinical Notes</label>
                <textarea name="clinical_notes" id="clinical_notes" rows="4" required
                    placeholder="Observations, examination findings…"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('clinical_notes') }}</textarea>
                @error('clinical_notes')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="diagnosis" class="block text-sm font-medium text-ink-body mb-2">
                    Diagnosis <span class="text-muted font-normal">(optional — can be added later)</span>
                </label>
                <textarea name="diagnosis" id="diagnosis" rows="2"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('diagnosis') }}</textarea>
                @error('diagnosis')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="plan" class="block text-sm font-medium text-ink-body mb-2">
                    Treatment Plan <span class="text-muted font-normal">(optional)</span>
                </label>
                <textarea name="plan" id="plan" rows="2"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('plan') }}</textarea>
                @error('plan')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('consultations.index') }}" class="px-4 py-2.5 text-sm font-medium text-muted hover:text-ink">Cancel</a>
                <x-submit-button loadingText="Starting…"
                    class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                    Start Consultation
                </x-submit-button>
            </div>
        </form>
    </div>
@endsection
