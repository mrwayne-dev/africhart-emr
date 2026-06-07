@extends('layouts.guest')

@section('title', 'Reset password — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6">
            <h1 class="text-xl font-medium text-ink tracking-tight">Set a new password</h1>
            <p class="text-sm text-muted mt-1">Choose a new password for your account.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium text-ink-body mb-2">Email address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required readonly
                    class="w-full bg-warm rounded text-sm text-muted px-4 py-3 border border-transparent focus:outline-none">
                @error('email')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            <x-password-input name="password" label="New Password" autocomplete="new-password" />
            <x-password-input name="password_confirmation" label="Confirm New Password" autocomplete="new-password" />

            <x-submit-button loadingText="Resetting…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Reset password
            </x-submit-button>
        </form>
    </div>
@endsection
