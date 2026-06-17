@extends('layouts.app')

@section('title', 'Drug Catalog — AfriChart EMR')
@section('page-title', 'Drug Catalog')
@section('page-subtitle', 'Medications & default prices used when billing prescriptions')

@section('content')
@php $currency = config('billing.currency_symbol', '₦'); @endphp
<div x-data="{
        open: false,
        mode: 'create',
        action: '{{ route('medications.store') }}',
        form: { id: null, name: '', default_price: '', common_frequency: '', dosages: '', routes: '' },
        openCreate() {
            this.mode = 'create';
            this.action = '{{ route('medications.store') }}';
            this.form = { id: null, name: '', default_price: '', common_frequency: '', dosages: '', routes: '' };
            this.open = true;
        },
        openEdit(med) {
            this.mode = 'edit';
            this.action = '/drug-catalog/' + med.id;
            this.form = {
                id: med.id,
                name: med.name,
                default_price: med.default_price,
                common_frequency: med.common_frequency ?? '',
                dosages: (med.dosages ?? []).join(', '),
                routes: (med.routes ?? []).join(', '),
            };
            this.open = true;
        },
     }"
     @open-edit-medication.window="openEdit($event.detail)">

    {{-- Search + add --}}
    <div class="bg-page border border-line rounded-card p-4 mb-6">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <form method="GET" action="{{ route('medications.index') }}" class="flex-1 flex items-center gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search medications…"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                <button type="submit"
                    class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors shrink-0">
                    Search
                </button>
            </form>
            <button type="button" @click="openCreate()"
                class="inline-flex items-center justify-center gap-1.5 bg-ink text-white rounded-full px-4 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors shrink-0">
                <x-phosphor-plus class="w-4 h-4" />
                Add Medication
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-page border border-line rounded-card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                        <th class="px-4 py-3 font-medium">Medication</th>
                        <th class="px-4 py-3 font-medium">Default Price</th>
                        <th class="px-4 py-3 font-medium">Common Dosages</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($medications as $medication)
                        <tr class="border-b border-line last:border-0 even:bg-warm/60">
                            <td class="px-4 py-3 font-medium text-ink">{{ $medication->name }}</td>
                            <td class="px-4 py-3 text-ink-body">{{ $currency }}{{ number_format((float) $medication->default_price, 2) }}</td>
                            <td class="px-4 py-3 text-muted">{{ implode(', ', $medication->dosages ?? []) ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                    {{ $medication->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $medication->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" title="Edit" class="text-muted hover:text-ink"
                                        @click="$dispatch('open-edit-medication', @js([
                                            'id' => $medication->id,
                                            'name' => $medication->name,
                                            'default_price' => $medication->default_price,
                                            'common_frequency' => $medication->common_frequency,
                                            'dosages' => $medication->dosages ?? [],
                                            'routes' => $medication->routes ?? [],
                                        ]))">
                                        <x-phosphor-pencil-simple class="w-4 h-4" />
                                    </button>
                                    <form method="POST" action="{{ route('medications.toggle', $medication) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-muted hover:text-ink"
                                            title="{{ $medication->is_active ? 'Deactivate' : 'Re-activate' }}">
                                            @if ($medication->is_active)
                                                <x-phosphor-eye-slash class="w-4 h-4" />
                                            @else
                                                <x-phosphor-eye class="w-4 h-4" />
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-muted">
                                No medications yet — add your first to start pricing prescriptions.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($medications->hasPages())
            <div class="px-6 py-4 border-t border-line">
                {{ $medications->links() }}
            </div>
        @endif
    </div>

    {{-- Add / Edit modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/40" @click="open = false"></div>

        <div x-show="open" x-transition
            class="relative bg-page rounded-card border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-page">
                <h2 class="text-base font-medium text-ink tracking-tight"
                    x-text="mode === 'create' ? 'Add Medication' : 'Edit Medication'"></h2>
                <button type="button" @click="open = false" class="text-muted hover:text-ink">
                    <x-phosphor-x class="w-5 h-5" />
                </button>
            </div>

            <form :action="action" method="POST" class="p-6 space-y-5">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Name</label>
                    <input type="text" name="name" x-model="form.name" required
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    @error('name')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Default Price ({{ $currency }})</label>
                    <input type="number" step="0.01" min="0" name="default_price" x-model="form.default_price" required
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    @error('default_price')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">
                        Common Dosages <span class="text-muted font-normal">(comma-separated, optional)</span>
                    </label>
                    <input type="text" name="dosages" x-model="form.dosages" placeholder="500mg, 1000mg"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">
                        Routes <span class="text-muted font-normal">(comma-separated, optional)</span>
                    </label>
                    <input type="text" name="routes" x-model="form.routes" placeholder="oral, iv"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">
                        Common Frequency <span class="text-muted font-normal">(optional)</span>
                    </label>
                    <input type="text" name="common_frequency" x-model="form.common_frequency" placeholder="3 times daily"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="text-sm text-muted hover:text-ink transition-colors px-2">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors">
                        <span x-text="mode === 'create' ? 'Add Medication' : 'Save Changes'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => window.toast('error', @js($errors->first())));
    </script>
@endif
@endsection
