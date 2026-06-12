{{-- Vitals entry fields. Used inside the "Record Vitals" modal on the show page. --}}
<form method="POST" action="{{ route('consultations.vitals', $consultation) }}" class="space-y-5"
    x-data="{ loading: false }" @submit="loading = true">
    @csrf
    @method('PATCH')

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="temperature" class="block text-sm font-medium text-ink-body mb-2">Temperature (°C)</label>
            <input type="number" step="0.1" name="temperature" id="temperature"
                value="{{ old('temperature', $consultation->temperature) }}" placeholder="37.0"
                class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('temperature')<p class="mt-1 text-xs text-accent">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="blood_pressure" class="block text-sm font-medium text-ink-body mb-2">Blood Pressure</label>
            <input type="text" name="blood_pressure" id="blood_pressure"
                value="{{ old('blood_pressure', $consultation->blood_pressure) }}" placeholder="120/80"
                class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('blood_pressure')<p class="mt-1 text-xs text-accent">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="pulse_rate" class="block text-sm font-medium text-ink-body mb-2">Pulse (bpm)</label>
            <input type="number" name="pulse_rate" id="pulse_rate"
                value="{{ old('pulse_rate', $consultation->pulse_rate) }}" placeholder="72"
                class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('pulse_rate')<p class="mt-1 text-xs text-accent">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="weight" class="block text-sm font-medium text-ink-body mb-2">Weight (kg)</label>
            <input type="number" step="0.1" name="weight" id="weight"
                value="{{ old('weight', $consultation->weight) }}" placeholder="68.0"
                class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('weight')<p class="mt-1 text-xs text-accent">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="height" class="block text-sm font-medium text-ink-body mb-2">Height (cm)</label>
            <input type="number" step="0.1" name="height" id="height"
                value="{{ old('height', $consultation->height) }}" placeholder="170.0"
                class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            @error('height')<p class="mt-1 text-xs text-accent">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="vitals_notes" class="block text-sm font-medium text-ink-body mb-2">
            Notes <span class="text-muted font-normal">(optional)</span>
        </label>
        <textarea name="vitals_notes" id="vitals_notes" rows="2"
            class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('vitals_notes', $consultation->vitals_notes) }}</textarea>
        @error('vitals_notes')<p class="mt-1 text-xs text-accent">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" @click="$dispatch('close-modal', 'vitals')"
            class="px-4 py-2.5 text-sm font-medium text-muted hover:text-ink">Cancel</button>
        <x-submit-button loadingText="Saving…"
            class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
            Save Vitals
        </x-submit-button>
    </div>
</form>
