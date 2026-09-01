@extends('layouts.app')

@section('title', 'Clinic Profile — Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Clinic Profile')

@section('content')
<x-settings-shell active="profile">

    <form method="POST" action="{{ route('settings.profile.update') }}"
        x-data="{ loading: false }" @submit="loading = true">
        @csrf
        @method('PUT')

        {{-- ── Identity ─────────────────────────────────────────────── --}}
        <div class="bg-page border border-line rounded-card">
            <div class="px-6 py-5 border-b border-line">
                <h2 class="text-base font-medium text-ink">Clinic details</h2>
                <p class="text-sm text-muted mt-1">
                    Your clinic's name appears on invoices, on every page your staff use, and in the
                    emails we send them.
                </p>
            </div>

            <div class="p-6 space-y-5">
                <x-form-field name="name" label="Clinic name" :value="$clinic->name" required
                    hint="This is the name patients see on their invoices." />

                <x-form-field name="address" label="Address" :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_ADDRESS)"
                    rows="2" optional
                    hint="Printed on invoices, under your clinic name." />

                <div class="grid sm:grid-cols-2 gap-5">
                    <x-form-field name="phone" label="Phone" type="tel" optional
                        :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_PHONE)"
                        placeholder="0803 123 4567" inputmode="tel" />

                    <x-form-field name="email" label="Email" type="email" optional
                        :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_EMAIL)"
                        placeholder="hello@yourclinic.com" />
                </div>
            </div>
        </div>

        {{-- ── Operations ───────────────────────────────────────────── --}}
        <div class="bg-page border border-line rounded-card mt-6">
            <div class="px-6 py-5 border-b border-line">
                <h2 class="text-base font-medium text-ink">Consultation & timing</h2>
            </div>

            <div class="p-6 space-y-5">
                <x-form-field name="consultation_fee" label="Default consultation fee"
                    type="number" step="0.01" min="0"
                    :prefix="config('billing.currency_symbol')"
                    :value="\App\Models\Setting::get(\App\Models\Setting::CONSULTATION_FEE, config('billing.consultation_fee'))"
                    hint="Added as the first line on every invoice generated from a consultation. Reception can still change it per invoice. Enter 0 if you do not charge one." />

                {{-- The timezone is not decoration: it decides what "today"
                     means to this clinic, which drives the dashboard's daily
                     counts and the queue's daily numbering. --}}
                <x-form-field name="timezone" label="Clinic timezone"
                    :options="$timezones"
                    :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_TIMEZONE, \App\Models\Setting::DEFAULT_TIMEZONE)"
                    hint="Decides where your clinic's day starts and ends — used for today's queue numbering and your dashboard counts." />
            </div>
        </div>

        {{-- ── Identifiers ──────────────────────────────────────────── --}}
        <div class="bg-page border border-line rounded-card mt-6">
            <div class="px-6 py-5 border-b border-line">
                <h2 class="text-base font-medium text-ink">Record numbering</h2>
                <p class="text-sm text-muted mt-1">
                    The prefix on your patient IDs, consultation IDs and invoice numbers.
                </p>
            </div>

            <div class="p-6">
                <x-form-field name="id_prefix" label="Identifier prefix"
                    :value="$clinic->id_prefix" :readonly="! $canEditPrefix"
                    maxlength="12"
                    :hint="$canEditPrefix
                        ? 'Uppercase letters and digits, 2–12 characters. Example: '.$clinic->id_prefix.'-20260901-0001. You can change this until your first record is created.'
                        : null" />

                @unless ($canEditPrefix)
                    {{--
                        Locked, with the reason stated.

                        The prefix is stamped into every identifier already
                        issued — on invoices patients keep and IDs quoted down
                        the phone. Changing it now would not renumber those; it
                        would strand them under a prefix the clinic no longer
                        uses. A field that silently refuses to save would be
                        worse than one that explains itself.
                    --}}
                    <div class="flex items-start gap-3 mt-4 bg-warm rounded-card p-4">
                        <x-phosphor-lock-simple class="w-5 h-5 text-muted shrink-0 mt-0.5" aria-hidden="true" />
                        <p class="text-sm text-muted leading-relaxed">
                            This can no longer be changed. Your clinic has
                            <strong class="text-ink-body">{{ number_format($recordCount) }}</strong>
                            {{ Str::plural('record', $recordCount) }} already carrying the
                            <strong class="text-ink-body">{{ $clinic->id_prefix }}</strong> prefix — on invoices your
                            patients keep and IDs your staff quote. Changing it would leave those
                            behind rather than renumber them.
                            <span class="block mt-1">Contact support if it genuinely needs to change.</span>
                        </p>
                    </div>
                @endunless
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <x-submit-button loadingText="Saving…"
                class="bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90">
                Save changes
            </x-submit-button>

            <a href="{{ route('dashboard') }}" class="text-sm text-muted hover:text-ink transition-colors">Cancel</a>
        </div>
    </form>

</x-settings-shell>
@endsection
