@extends('layouts.app')

@section('title', 'Edit Consultation — AfriChart EMR')
@section('page-title', 'Edit Consultation')
@section('page-subtitle', $consultation->consultation_id)

@section('content')
    <a href="{{ route('consultations.show', $consultation) }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink mb-6">
        <x-phosphor-arrow-left class="w-4 h-4" /> Back to consultation
    </a>

    <div class="bg-page border border-line rounded-card p-6 max-w-2xl">
        <p class="text-sm text-muted mb-5">Patient: <span class="text-ink font-medium">{{ $consultation->patient?->full_name }}</span></p>

        <form method="POST" action="{{ route('consultations.update', $consultation) }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf
            @method('PUT')

            <div>
                <label for="chief_complaint" class="block text-sm font-medium text-ink-body mb-2">Chief Complaint</label>
                <textarea name="chief_complaint" id="chief_complaint" rows="2" required
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('chief_complaint', $consultation->chief_complaint) }}</textarea>
                @error('chief_complaint')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="clinical_notes" class="block text-sm font-medium text-ink-body mb-2">Clinical Notes</label>
                <textarea name="clinical_notes" id="clinical_notes" rows="4" required
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('clinical_notes', $consultation->clinical_notes) }}</textarea>
                @error('clinical_notes')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="diagnosis" class="block text-sm font-medium text-ink-body mb-2">Diagnosis</label>
                <textarea name="diagnosis" id="diagnosis" rows="2"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('diagnosis', $consultation->diagnosis) }}</textarea>
                @error('diagnosis')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="plan" class="block text-sm font-medium text-ink-body mb-2">Treatment Plan</label>
                <textarea name="plan" id="plan" rows="2"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('plan', $consultation->plan) }}</textarea>
                @error('plan')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-ink-body mb-2">Status</label>
                <select name="status" id="status"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    @foreach (\App\Enums\ConsultationStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('status', $consultation->status->value) === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('consultations.show', $consultation) }}" class="px-4 py-2.5 text-sm font-medium text-muted hover:text-ink">Cancel</a>
                <x-submit-button loadingText="Saving…"
                    class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                    Save Changes
                </x-submit-button>
            </div>
        </form>
    </div>
@endsection
