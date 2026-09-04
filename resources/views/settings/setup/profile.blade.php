@extends('layouts.app')

@section('title', 'Set up your clinic')
@section('page-title', 'Welcome')
@section('page-subtitle', 'Let\'s finish setting up ' . $clinicName)

@section('content')
<x-setup-shell :step="$step" :progress="$progress"
    title="Clinic details"
    lead="These appear on the invoices your patients receive. Your clinic's name and address were set when we created your account — we only need what is missing.">

    <form method="POST" action="{{ route('setup.profile.store') }}" class="space-y-5"
        x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <x-form-field name="consultation_fee" label="Consultation fee" type="number" required
            :value="\App\Models\Setting::get(\App\Models\Setting::CONSULTATION_FEE)"
            step="0.01" min="0" prefix="₦"
            hint="The default added to a new invoice. You can change it per patient." />

        <x-form-field name="address" label="Clinic address"
            :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_ADDRESS)"
            placeholder="12 Aba Road, Port Harcourt"
            hint="Printed on invoices, so patients know where the bill came from." />

        <div class="grid sm:grid-cols-2 gap-5">
            <x-form-field name="phone" label="Clinic phone" type="tel"
                :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_PHONE)"
                placeholder="0803 123 4567" />

            <x-form-field name="email" label="Clinic email" type="email"
                :value="\App\Models\Setting::get(\App\Models\Setting::CLINIC_EMAIL)"
                placeholder="billing@clinic.com" />
        </div>

        <x-form-field name="timezone" label="Timezone" required
            :value="\App\Support\ClinicIdentity::timezone()"
            :options="collect(['Africa/Lagos','Africa/Abidjan','Africa/Accra','Africa/Nairobi','UTC'])
                ->mapWithKeys(fn ($tz) => [$tz => $tz])->all()"
            hint="Decides what “today” means for your queue and your daily figures." />

        <div class="flex items-center gap-3 pt-2">
            <x-submit-button loadingText="Saving…"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                Continue
            </x-submit-button>
        </div>
    </form>
</x-setup-shell>
@endsection
