@props([
    'consultations',
])

{{-- Reception billing worklist: completed consultations with no invoice yet.
     Billing-relevant fields only — no clinical notes/diagnosis. --}}
<div class="bg-page border border-line rounded-card">
    <div class="px-6 py-4 border-b border-line flex items-center justify-between">
        <h2 class="text-base font-medium text-ink tracking-tight">
            Ready to Invoice
            <span class="ml-2 text-sm text-muted font-normal">{{ $consultations->count() }} {{ Str::plural('consultation', $consultations->count()) }}</span>
        </h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                    <th class="px-4 py-3 font-medium">Patient</th>
                    <th class="px-4 py-3 font-medium">Doctor</th>
                    <th class="px-4 py-3 font-medium whitespace-nowrap">Completed</th>
                    <th class="px-4 py-3 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($consultations as $consultation)
                    <tr class="border-b border-line last:border-0 even:bg-warm/60">
                        <td class="px-4 py-3 text-ink">
                            {{ $consultation->patient?->full_name ?? '—' }}
                            <span class="block text-xs text-muted">{{ $consultation->patient?->patient_id }}</span>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $consultation->doctor?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $consultation->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @can('create', \App\Models\Invoice::class)
                                <form method="POST" action="{{ route('invoices.generate', $consultation) }}"
                                    x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <x-submit-button loadingText="Generating…"
                                        class="bg-ink text-white rounded-full px-4 py-2 text-xs font-medium hover:bg-ink/90">
                                        Generate Invoice
                                    </x-submit-button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-muted">
                            No completed consultations awaiting an invoice.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
