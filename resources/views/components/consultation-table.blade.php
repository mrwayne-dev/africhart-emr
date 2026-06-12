@props([
    'consultations',
])

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                <th class="px-4 py-3 font-medium">Consultation ID</th>
                <th class="px-4 py-3 font-medium">Patient</th>
                <th class="px-4 py-3 font-medium">Doctor</th>
                <th class="px-4 py-3 font-medium">Diagnosis</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium whitespace-nowrap">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($consultations as $consultation)
                <tr class="border-b border-line last:border-0 even:bg-warm/60 hover:bg-warm transition-colors cursor-pointer"
                    onclick="window.location='{{ route('consultations.show', $consultation) }}'">
                    <td class="px-4 py-3 font-medium text-ink whitespace-nowrap">{{ $consultation->consultation_id }}</td>
                    <td class="px-4 py-3 text-ink">{{ $consultation->patient?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted">{{ $consultation->doctor?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted max-w-[14rem] truncate">{{ $consultation->diagnosis ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $consultation->status->color() }}">
                            {{ $consultation->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $consultation->created_at->format('j M Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-muted">No consultations found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
