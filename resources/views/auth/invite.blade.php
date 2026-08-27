@extends('layouts.guest')

@section('title', 'Accept your invitation — AfriChart EMR')

{{-- The URL contains a single-use token: never canonicalised, never indexed. --}}
@section('private-url', '1')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6">
            <p class="font-mono text-xs text-muted uppercase tracking-[0.2em] mb-3">
                <span class="text-accent">[</span> Invitation <span class="text-accent">]</span>
            </p>

            <h1 class="text-xl font-medium text-ink tracking-tight">
                You've been invited to join {{ $clinicName }}
            </h1>

            {{-- The role is stated, not chosen. It came from the invitation and
                 there is no control here that could change it. --}}
            <p class="text-sm text-muted mt-2 leading-relaxed">
                as a <span class="text-ink-body font-medium">{{ $invitation->role->label() }}</span>.
                Set a password below to activate your account.
            </p>
        </div>

        <form method="POST" action="{{ route('invite.accept', ['token' => $token]) }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf

            {{-- Email, shown but not editable.

                 Read-only rather than a hidden field: the person should see
                 which address they are activating, but changing it would mean
                 the invitation the admin approved enrolled someone else. The
                 server never reads this input in any case — the address comes
                 from the invitation record — so it carries no name attribute at
                 all and cannot be posted. --}}
            <div>
                <label for="email" class="block text-sm font-medium text-ink-body mb-2">Email address</label>
                <input type="email" id="email" value="{{ $invitation->email }}" readonly disabled
                    class="w-full bg-warm/60 rounded text-sm text-muted px-4 py-3 border border-transparent cursor-not-allowed">
                <p class="mt-2 text-xs text-muted">Your invitation was sent to this address.</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-ink-body mb-2">Your full name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $invitation->name) }}"
                    required autofocus autocomplete="name"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('name')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-password-input name="password" label="Choose a password" autocomplete="new-password" />
                <x-password-strength for="password" />
            </div>

            <x-password-input name="password_confirmation" label="Confirm password" autocomplete="new-password" />

            <x-submit-button loadingText="Activating…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Activate my account
            </x-submit-button>
        </form>

        <p class="text-xs text-muted text-center mt-6 leading-relaxed">
            This invitation expires
            {{ $invitation->expires_at->diffForHumans(['parts' => 1]) }}
            and can only be used once.
        </p>
    </div>
@endsection
