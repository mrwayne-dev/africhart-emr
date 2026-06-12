@props([
    'patients',
    'doctors',
])

{{-- Open with: $dispatch('open-modal', 'check-in') --}}
<x-modal name="check-in" title="Check In Patient">
    <form method="POST" action="{{ route('queue.store') }}" class="space-y-5"
        x-data="{ loading: false }" @submit="loading = true">
        @csrf

        {{-- Patient --}}
        <div>
            <label for="patient_id" class="block text-sm font-medium text-ink-body mb-2">Patient</label>
            <select name="patient_id" id="patient_id" required
                class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                    focus:bg-page focus:border-ink focus:outline-none transition-colors">
                <option value="">Select a patient…</option>
                @foreach ($patients as $patient)
                    <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                        {{ $patient->full_name }} ({{ $patient->patient_id }})
                    </option>
                @endforeach
            </select>
            @error('patient_id')
                <p class="mt-2 text-sm text-accent">{{ $message }}</p>
            @enderror
        </div>

        {{-- Assign doctor (optional) --}}
        <div>
            <label for="assigned_doctor_id" class="block text-sm font-medium text-ink-body mb-2">
                Assign Doctor <span class="text-muted font-normal">(optional)</span>
            </label>
            <select name="assigned_doctor_id" id="assigned_doctor_id"
                class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                    focus:bg-page focus:border-ink focus:outline-none transition-colors">
                <option value="">Unassigned</option>
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected(old('assigned_doctor_id') == $doctor->id)>{{ $doctor->name }}</option>
                @endforeach
            </select>
            @error('assigned_doctor_id')
                <p class="mt-2 text-sm text-accent">{{ $message }}</p>
            @enderror
        </div>

        {{-- Reason --}}
        <div>
            <label for="reason" class="block text-sm font-medium text-ink-body mb-2">
                Reason for visit <span class="text-muted font-normal">(optional)</span>
            </label>
            <textarea name="reason" id="reason" rows="2"
                class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                    focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('reason') }}</textarea>
            @error('reason')
                <p class="mt-2 text-sm text-accent">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" @click="$dispatch('close-modal', 'check-in')"
                class="px-4 py-2.5 text-sm font-medium text-muted hover:text-ink transition-colors">
                Cancel
            </button>
            <x-submit-button loadingText="Checking in…"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                Check In
            </x-submit-button>
        </div>
    </form>
</x-modal>

@if ($errors->hasAny(['patient_id', 'assigned_doctor_id', 'reason']))
    <script>
        document.addEventListener('DOMContentLoaded', () =>
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'check-in' }))
        );
    </script>
@endif
