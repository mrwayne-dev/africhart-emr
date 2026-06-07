@extends('layouts.guest')

@section('title', 'Sign in — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6">
            <h1 class="text-xl font-medium text-ink tracking-tight">Welcome back</h1>
            <p class="text-sm text-muted mt-1">Sign in to access the clinic dashboard.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-ink-body mb-2">Email address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                    autofocus required autocomplete="email"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('email')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password (with show/hide toggle) --}}
            <x-password-input name="password" label="Password" autocomplete="current-password" />

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" name="remember" class="rounded border-line text-ink focus:ring-0">
                    Remember me
                </label>
                <a href="{{ route('password.request') }}" class="text-sm text-muted hover:text-ink transition-colors">
                    Forgot password?
                </a>
            </div>

            <x-submit-button loadingText="Signing in…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Sign in
            </x-submit-button>
        </form>

        <p class="text-center text-sm text-muted mt-6">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-ink font-medium hover:underline">Create one</a>
        </p>
    </div>
@endsection
