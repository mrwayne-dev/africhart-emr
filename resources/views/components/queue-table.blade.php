@props([
    'queue',
    'doctors' => [],
])

@php
    $user = auth()->user();
    $canAssign = $user->isAdmin() || $user->isNurse() || $user->isReceptionist();
    $canCancel = $user->isAdmin() || $user->isReceptionist();
    $showActions = ($canAssign || $canCancel) && count($doctors) > 0;
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                <th class="px-4 py-3 font-medium">#</th>
                <th class="px-4 py-3 font-medium">Patient</th>
                <th class="px-4 py-3 font-medium">Reason</th>
                <th class="px-4 py-3 font-medium">Assigned Doctor</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium whitespace-nowrap">Checked In</th>
                @if ($showActions)
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($queue as $entry)
                <tr class="border-b border-line last:border-0 even:bg-warm/60">
                    <td class="px-4 py-3 font-medium text-ink">{{ $entry->queue_number }}</td>
                    <td class="px-4 py-3 text-ink">
                        <a href="{{ route('patients.show', $entry->patient_id) }}" class="hover:underline">
                            {{ $entry->patient?->full_name ?? '—' }}
                        </a>
                        <span class="block text-xs text-muted">{{ $entry->patient?->patient_id }}</span>
                    </td>
                    <td class="px-4 py-3 text-muted max-w-[12rem] truncate">{{ $entry->reason ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted">{{ $entry->assignedDoctor?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $entry->status->color() }}">
                            {{ $entry->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $entry->checked_in_at?->format('g:i A') }}</td>
                    @if ($showActions)
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                @if ($canAssign && in_array($entry->status, [\App\Enums\QueueStatus::Waiting, \App\Enums\QueueStatus::InConsultation]))
                                    <form method="POST" action="{{ route('queue.assign', $entry) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="assigned_doctor_id" required
                                            class="bg-warm rounded text-xs text-ink-body px-2 py-1.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                                            <option value="">{{ $entry->assigned_doctor_id ? 'Reassign…' : 'Assign…' }}</option>
                                            @foreach ($doctors as $doctor)
                                                <option value="{{ $doctor->id }}" @selected($entry->assigned_doctor_id === $doctor->id)>{{ $doctor->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="text-muted hover:text-ink" title="Save assignment">
                                            <x-phosphor-check class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif

                                @if ($canCancel && in_array($entry->status, [\App\Enums\QueueStatus::Waiting, \App\Enums\QueueStatus::InConsultation]))
                                    <form method="POST" action="{{ route('queue.cancel', $entry) }}"
                                        onsubmit="return confirm('Cancel this queue entry?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-muted hover:text-accent" title="Cancel">
                                            <x-phosphor-x-circle class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showActions ? 7 : 6 }}" class="px-4 py-10 text-center text-muted">
                        No patients in today's queue.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
