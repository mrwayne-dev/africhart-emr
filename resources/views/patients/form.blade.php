@php
    $patient ??= null;
@endphp

<div class="space-y-5">
    {{-- Full name --}}
    <div>
        <label for="full_name" class="block text-sm font-medium text-ink-body mb-2">Full Name</label>
        <input type="text" name="full_name" id="full_name"
            value="{{ old('full_name', $patient?->full_name) }}" required
            class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                focus:bg-page focus:border-ink focus:outline-none transition-colors">
        @error('full_name')
            <p class="mt-2 text-sm text-accent">{{ $message }}</p>
        @enderror
    </div>

    {{-- Date of birth --}}
    <div>
        <label for="date_of_birth" class="block text-sm font-medium text-ink-body mb-2">Date of Birth</label>
        <input type="date" name="date_of_birth" id="date_of_birth" max="{{ now()->format('Y-m-d') }}"
            value="{{ old('date_of_birth', $patient?->date_of_birth?->format('Y-m-d')) }}" required
            class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                focus:bg-page focus:border-ink focus:outline-none transition-colors">
        @error('date_of_birth')
            <p class="mt-2 text-sm text-accent">{{ $message }}</p>
        @enderror
    </div>

    {{-- Gender --}}
    <div>
        <label for="gender" class="block text-sm font-medium text-ink-body mb-2">Gender</label>
        <select name="gender" id="gender"
            class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-ink-body focus:border-brand focus:ring-2 focus:ring-brand/20">
            <option value="">Prefer not to say</option>
            @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" @selected(old('gender', $patient->gender ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('gender')
            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>


    {{-- Phone --}}
    <div>
        <label for="phone" class="block text-sm font-medium text-ink-body mb-2">Phone Number</label>
        <input type="tel" name="phone" id="phone"
            value="{{ old('phone', $patient?->phone) }}" placeholder="08031234567" required
            class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                focus:bg-page focus:border-ink focus:outline-none transition-colors">
        @error('phone')
            <p class="mt-2 text-sm text-accent">{{ $message }}</p>
        @enderror
    </div>

    {{-- Blood group --}}
    <div>
        <label for="blood_group" class="block text-sm font-medium text-ink-body mb-2">Blood Group</label>
        <select name="blood_group" id="blood_group" required
            class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                focus:bg-page focus:border-ink focus:outline-none transition-colors">
            <option value="">Select blood group</option>
            @foreach (\App\Enums\BloodGroup::cases() as $group)
                <option value="{{ $group->value }}"
                    @selected(old('blood_group', $patient?->blood_group?->value) === $group->value)>
                    {{ $group->label() }}
                </option>
            @endforeach
        </select>
        @error('blood_group')
            <p class="mt-2 text-sm text-accent">{{ $message }}</p>
        @enderror
    </div>

    {{-- Allergies --}}
    <div>
        <label for="allergies" class="block text-sm font-medium text-ink-body mb-2">
            Known Allergies <span class="text-muted font-normal">(optional)</span>
        </label>
        <textarea name="allergies" id="allergies" rows="3" placeholder="e.g. Penicillin, dust, latex..."
            class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                focus:bg-page focus:border-ink focus:outline-none transition-colors">{{ old('allergies', $patient?->allergies) }}</textarea>
        @error('allergies')
            <p class="mt-2 text-sm text-accent">{{ $message }}</p>
        @enderror
    </div>

    {{-- Emergency contact — who to call when the patient cannot speak for themselves --}}
    <div class="sm:col-span-2 pt-2 mt-2 border-t border-line">
        <h3 class="text-sm font-semibold text-ink-body mb-1">Emergency contact</h3>
        <p class="text-xs text-ink-muted mb-4">Optional, but the first thing needed if this patient deteriorates.</p>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="emergency_contact_name" class="block text-sm font-medium text-ink-body mb-2">Name</label>
                <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                    value="{{ old('emergency_contact_name', $patient->emergency_contact_name ?? '') }}"
                    placeholder="e.g. Emeka Eze"
                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-ink-body focus:border-brand focus:ring-2 focus:ring-brand/20">
                @error('emergency_contact_name')
                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="emergency_contact_phone" class="block text-sm font-medium text-ink-body mb-2">Phone</label>
                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                    value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone ?? '') }}"
                    placeholder="e.g. 08099887766"
                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-ink-body focus:border-brand focus:ring-2 focus:ring-brand/20">
                @error('emergency_contact_phone')
                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="emergency_contact_relationship" class="block text-sm font-medium text-ink-body mb-2">Relationship</label>
                <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship"
                    value="{{ old('emergency_contact_relationship', $patient->emergency_contact_relationship ?? '') }}"
                    placeholder="e.g. Husband, sister, neighbour"
                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-ink-body focus:border-brand focus:ring-2 focus:ring-brand/20">
                @error('emergency_contact_relationship')
                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

</div>
