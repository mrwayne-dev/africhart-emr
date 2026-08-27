@extends('layouts.guest')

@section('title', 'Sign in — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6">
            <h1 class="text-xl font-medium text-ink tracking-tight">Welcome back</h1>
            <p class="text-sm text-muted mt-1">Sign in to access the clinic dashboard.</p>
        </div>

        {{-- Throttled at 5/min on the route. Laravel returns the lockout as a
             validation error on `email`, which reads as "wrong password" unless
             it is surfaced separately — so it gets its own notice above the
             form rather than hiding under a field the user did not get wrong. --}}
        @if ($errors->has('throttle'))
            <div class="flex items-start gap-3 border border-line rounded-card p-4 mb-6" role="alert">
                <x-phosphor-clock class="w-5 h-5 text-ink shrink-0 mt-0.5" aria-hidden="true" />
                <p class="text-sm text-muted leading-relaxed">{{ $errors->first('throttle') }}</p>
            </div>
        @endif

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

        <div class="mt-6 pt-5 border-t border-line flex flex-col gap-2 text-center">
            {{-- No link: joining a clinic is not something you can start from
                 here any more. An invitation arrives by email with a link that
                 carries its own credential, so there is no page to send someone
                 to and nothing for them to type. --}}
            <p class="text-sm text-muted">
                Joining a clinic already on AfriChart? Your administrator sends you
                an invitation by email.
            </p>
            <p class="text-sm text-muted">
                New clinic?
                <a href="{{ route('signup') }}" class="text-ink font-medium hover:underline">Get started &rarr;</a>
            </p>
        </div>
    </div>
@endsection
