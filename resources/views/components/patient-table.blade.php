@props([
    'patients',
    'editable' => false,
    'archived' => false,
])

@php $showActions = $editable || $archived; @endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                <th class="px-4 py-3 font-medium">Patient ID</th>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Age</th>
                <th class="px-4 py-3 font-medium">Blood Group</th>
                <th class="px-4 py-3 font-medium">Phone</th>
                <th class="px-4 py-3 font-medium">Registered By</th>
                @if ($showActions)
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                <tr class="border-b border-line last:border-0 even:bg-warm/60 {{ $archived ? '' : 'hover:bg-warm transition-colors cursor-pointer' }}"
                    @unless ($archived) onclick="window.location='{{ route('patients.show', $patient) }}'" @endunless>
                    <td class="px-4 py-3 font-medium text-ink whitespace-nowrap">{{ $patient->patient_id }}</td>
                    <td class="px-4 py-3 text-ink">{{ $patient->full_name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $patient->age }}</td>
                    <td class="px-4 py-3 text-muted">{{ $patient->blood_group->label() }}</td>
                    <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $patient->phone }}</td>
                    <td class="px-4 py-3 text-muted">{{ $patient->registeredBy?->name ?? '—' }}</td>
                    @if ($showActions)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($archived)
                                <form method="POST" action="{{ route('patients.restore', $patient) }}"
                                    onsubmit="return confirm('Restore this patient record?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-1 text-muted hover:text-ink transition-colors">
                                        <x-phosphor-arrow-counter-clockwise class="w-4 h-4" />
                                        Restore
                                    </button>
                                </form>
                            @else
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
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showActions ? 7 : 6 }}" class="px-4 py-10 text-center text-muted">
                        {{ $archived ? 'No archived patients.' : 'No patients found.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
