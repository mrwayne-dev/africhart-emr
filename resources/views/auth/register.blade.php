@extends('layouts.guest')

@section('title', 'Create account — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8" x-data="{ role: '{{ old('role', 'doctor') }}' }">
        <div class="mb-6">
            <h1 class="text-xl font-medium text-ink tracking-tight">Create your account</h1>
            <p class="text-sm text-muted mt-1">Registration requires an invite code from your clinic.</p>
        </div>

        {{-- Role tabs --}}
        <div class="grid grid-cols-2 gap-1 p-1 bg-warm rounded-card mb-6">
            @foreach (['doctor' => 'Doctor', 'admin' => 'Admin', 'nurse' => 'Nurse', 'receptionist' => 'Receptionist'] as $value => $label)
                <button type="button" @click="role = '{{ $value }}'"
                    class="py-2 text-sm font-medium rounded transition-colors"
                    :class="role === '{{ $value }}' ? 'bg-page text-ink' : 'text-muted hover:text-ink'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <input type="hidden" name="role" :value="role">

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-ink-body mb-2">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" autofocus required
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('name')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-ink-body mb-2">Email address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('email')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password + confirm --}}
            <x-password-input name="password" label="Password" autocomplete="new-password" />
            <x-password-input name="password_confirmation" label="Confirm Password" autocomplete="new-password" />

            {{-- Invite code --}}
            <div>
                <label for="invite_code" class="block text-sm font-medium text-ink-body mb-2">
                    <span x-text="role.charAt(0).toUpperCase() + role.slice(1) + ' invite code'"></span>
                </label>
                <input type="text" name="invite_code" id="invite_code" value="{{ old('invite_code') }}" required
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('invite_code')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
                @error('role')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            <x-submit-button loadingText="Creating account…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Create account
            </x-submit-button>
        </form>

        <p class="text-center text-sm text-muted mt-6">
            Already have an account?
            <a href="{{ route('login') }}" class="text-ink font-medium hover:underline">Sign in</a>
        </p>
    </div>
@endsection
