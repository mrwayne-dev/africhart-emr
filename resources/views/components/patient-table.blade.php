@props([
    'patients',
    'editable' => false,
])

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-muted border-b border-line">
                <th class="px-4 py-3 font-medium">Patient ID</th>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Age</th>
                <th class="px-4 py-3 font-medium">Blood Group</th>
                <th class="px-4 py-3 font-medium">Phone</th>
                <th class="px-4 py-3 font-medium">Registered By</th>
                @if ($editable)
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                <tr class="border-b border-line last:border-0 even:bg-warm/60 hover:bg-warm transition-colors cursor-pointer"
                    onclick="window.location='{{ route('patients.show', $patient) }}'">
                    <td class="px-4 py-3 font-medium text-ink whitespace-nowrap">{{ $patient->patient_id }}</td>
                    <td class="px-4 py-3 text-ink">{{ $patient->full_name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $patient->age }}</td>
                    <td class="px-4 py-3 text-muted">{{ $patient->blood_group->label() }}</td>
                    <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $patient->phone }}</td>
                    <td class="px-4 py-3 text-muted">{{ $patient->registeredBy?->name ?? '—' }}</td>
                    @if ($editable)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button"
                                onclick="event.stopPropagation()"
                                @click="$dispatch('edit-patient', {
                                    id: {{ $patient->id }},
                                    full_name: @js($patient->full_name),
                                    date_of_birth: @js(optional($patient->date_of_birth)->format('Y-m-d')),
                                    phone: @js($patient->phone),
                                    blood_group: @js($patient->blood_group->value),
                                    allergies: @js($patient->allergies),
                                })"
                                class="inline-flex items-center gap-1 text-muted hover:text-ink transition-colors">
                                <x-phosphor-pencil-simple class="w-4 h-4" />
                                Edit
                            </button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $editable ? 7 : 6 }}" class="px-4 py-10 text-center text-muted">
                        No patients found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
