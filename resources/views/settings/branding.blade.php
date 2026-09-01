@extends('layouts.app')

@section('title', 'Branding — Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Branding')

@section('content')
<x-settings-shell active="branding">

    <div class="bg-page border border-line rounded-card">
        <div class="px-6 py-5 border-b border-line">
            <h2 class="text-base font-medium text-ink">Your logo</h2>
            <p class="text-sm text-muted mt-1">
                Shown on the invoices your patients receive, beside your clinic's name.
                {{-- Says plainly where the NAME is edited, so nobody hunts for it here. --}}
                Your clinic name is part of your profile and is edited in
                <a href="{{ route('settings.profile.edit') }}" class="text-ink font-medium hover:underline">Clinic Profile</a>.
            </p>
        </div>

        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-start gap-6">

                {{-- Current state. The no-logo case is a real state, not an
                     empty box: a clinic that has not uploaded one still has a
                     letterhead, and it says so. --}}
                <div class="shrink-0">
                    <p class="text-xs text-muted uppercase tracking-wide mb-2">Current</p>
                    <div class="w-40 h-40 rounded-card border border-line bg-warm flex items-center justify-center p-4">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $clinicName }} logo"
                                class="max-w-full max-h-full object-contain">
                        @else
                            <p class="text-sm text-muted text-center leading-snug">
                                No logo yet — invoices show<br>
                                <span class="text-ink-body font-medium">{{ $clinicName }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <form method="POST" action="{{ route('settings.branding.update') }}"
                        enctype="multipart/form-data" x-data="{ loading: false }" @submit="loading = true">
                        @csrf

                        <label for="logo" class="block text-sm font-medium text-ink-body mb-2">
                            Upload a logo
                        </label>

                        <input type="file" name="logo" id="logo" required
                            accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-sm text-ink-body
                                file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0
                                file:text-sm file:font-medium file:bg-ink file:text-white
                                hover:file:bg-ink/90 file:cursor-pointer
                                bg-warm rounded border border-transparent px-4 py-3
                                focus:bg-page focus:border-ink focus:outline-none transition-colors">

                        <p class="mt-2 text-xs text-muted">
                            PNG, JPG or WebP, up to 2 MB. A square or wide image works best.
                            SVG is not accepted — it can carry script, and this file is shown to patients.
                        </p>

                        @error('logo')
                            <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center gap-3 mt-5">
                            <x-submit-button loadingText="Uploading…"
                                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                                Save logo
                            </x-submit-button>
                        </div>
                    </form>

                    @if ($logoUrl)
                        <form method="POST" action="{{ route('settings.branding.destroy') }}" class="mt-4"
                            onsubmit="return confirm('Remove the logo? Invoices will show your clinic name instead.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-accent transition-colors">
                                <x-phosphor-trash class="w-4 h-4" aria-hidden="true" />
                                Remove logo
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-settings-shell>
@endsection
