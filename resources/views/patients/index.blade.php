@extends('layouts.app')

@section('title', 'Patients — AfriChart EMR')
@section('page-title', 'Patients')
@section('page-subtitle', 'All registered patients')

@section('content')
<div x-data="patientModal()" x-on:edit-patient.window="openEdit($event.detail)">

    {{-- Search & filter bar --}}
    <div class="bg-page border border-line rounded-card p-4 mb-6">
        <form method="GET" action="{{ route('patients.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by name, phone, or patient ID..."
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
            </div>

            <div class="w-full sm:w-48">
                <select name="blood_group"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    <option value="">All Blood Groups</option>
                    @foreach (\App\Enums\BloodGroup::cases() as $group)
                        <option value="{{ $group->value }}" @selected(request('blood_group') === $group->value)>
                            {{ $group->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
                Search
            </button>

            @if (request('search') || request('blood_group'))
                <a href="{{ route('patients.index') }}"
                    class="text-sm text-muted hover:text-ink transition-colors self-center px-2">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="bg-page border border-line rounded-card">
        <div class="px-6 py-4 border-b border-line flex items-center justify-between">
            <h2 class="text-base font-medium text-ink tracking-tight">
                {{ $patients->total() }} {{ Str::plural('patient', $patients->total()) }}
            </h2>
            <a href="{{ route('patients.create') }}" @click.prevent="openCreate()"
                class="inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors">
                <x-phosphor-plus class="w-4 h-4" />
                Register Patient
            </a>
        </div>

        <x-patient-table :patients="$patients" :editable="true" />

        @if ($patients->hasPages())
            <div class="px-6 py-4 border-t border-line">
                {{ $patients->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/40" @click="close()"></div>

        <div x-show="open" x-transition
            class="relative bg-page rounded-card border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-page">
                <h2 class="text-base font-medium text-ink tracking-tight"
                    x-text="mode === 'create' ? 'Register New Patient' : 'Edit Patient'"></h2>
                <button type="button" @click="close()" class="text-muted hover:text-ink">
                    <x-phosphor-x class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submit()" class="p-6 space-y-5">
                {{-- Full name --}}
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Full Name</label>
                    <input type="text" x-model="form.full_name"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                            focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    <p x-show="error('full_name')" x-text="error('full_name')" class="mt-2 text-sm text-accent"></p>
                </div>

                {{-- Date of birth --}}
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Date of Birth</label>
                    <input type="date" x-model="form.date_of_birth" max="{{ now()->format('Y-m-d') }}"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                            focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    <p x-show="error('date_of_birth')" x-text="error('date_of_birth')" class="mt-2 text-sm text-accent"></p>
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Phone Number</label>
                    <input type="tel" x-model="form.phone" placeholder="08031234567"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                            focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    <p x-show="error('phone')" x-text="error('phone')" class="mt-2 text-sm text-accent"></p>
                </div>

                {{-- Blood group --}}
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Blood Group</label>
                    <select x-model="form.blood_group"
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                            focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <option value="">Select blood group</option>
                        @foreach (\App\Enums\BloodGroup::cases() as $group)
                            <option value="{{ $group->value }}">{{ $group->label() }}</option>
                        @endforeach
                    </select>
                    <p x-show="error('blood_group')" x-text="error('blood_group')" class="mt-2 text-sm text-accent"></p>
                </div>

                {{-- Allergies --}}
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">
                        Known Allergies <span class="text-muted font-normal">(optional)</span>
                    </label>
                    <textarea x-model="form.allergies" rows="3" placeholder="e.g. Penicillin, dust, latex..."
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                            focus:bg-page focus:border-ink focus:outline-none transition-colors"></textarea>
                    <p x-show="error('allergies')" x-text="error('allergies')" class="mt-2 text-sm text-accent"></p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="close()" class="text-sm text-muted hover:text-ink transition-colors px-2">
                        Cancel
                    </button>
                    <button type="submit" :disabled="processing"
                        class="inline-flex items-center justify-center gap-2 bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <x-spinner x-show="processing" x-cloak class="w-4 h-4" />
                        <span x-text="processing ? 'Saving…' : (mode === 'create' ? 'Register Patient' : 'Save Changes')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
