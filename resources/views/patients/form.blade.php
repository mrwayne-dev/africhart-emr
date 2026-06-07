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
</div>
