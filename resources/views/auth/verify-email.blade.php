@extends('layouts.guest')

@section('title', 'Verify your email — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6 text-center">
            <div class="w-12 h-12 bg-warm rounded-full flex items-center justify-center mx-auto mb-4">
                <x-phosphor-envelope-simple class="w-6 h-6 text-ink" />
            </div>
            <h1 class="text-xl font-medium text-ink tracking-tight">Verify your email</h1>
            <p class="text-sm text-muted mt-1">
                We emailed a 6-digit code to<br>
                <span class="text-ink-body font-medium">{{ auth()->user()->email }}</span>
            </p>
        </div>

        <form method="POST" action="{{ route('verification.verify') }}" class="space-y-5"
            x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div>
                <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                    autofocus required placeholder="••••••"
                    class="w-full bg-warm rounded text-center text-2xl tracking-[0.5em] font-medium text-ink px-4 py-3
                        border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                @error('code')
                    <p class="mt-2 text-sm text-accent text-center">{{ $message }}</p>
                @enderror
            </div>

            <x-submit-button loadingText="Verifying…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Verify email
            </x-submit-button>
        </form>

        <div class="flex items-center justify-between mt-6 text-sm">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="text-muted hover:text-ink transition-colors">Resend code</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-muted hover:text-ink transition-colors">Log out</button>
            </form>
        </div>
    </div>
@endsection
