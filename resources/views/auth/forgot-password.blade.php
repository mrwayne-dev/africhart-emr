@extends('layouts.guest')

@section('title', 'Forgot password — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6">
            <h1 class="text-xl font-medium text-ink tracking-tight">Forgot your password?</h1>
            <p class="text-sm text-muted mt-1">Enter your email and we'll send you a reset link.</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-ink-body mb-2">Email address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" autofocus required
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('email')
                    <p class="mt-2 text-sm text-accent">{{ $message }}</p>
                @enderror
            </div>

            <x-submit-button loadingText="Sending…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Send reset link
            </x-submit-button>
        </form>

        <p class="text-center text-sm text-muted mt-6">
            <a href="{{ route('login') }}" class="text-ink font-medium hover:underline">Back to sign in</a>
        </p>
    </div>
@endsection
