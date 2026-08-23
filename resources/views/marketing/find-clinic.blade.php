@extends('layouts.guest')

@section('title', 'Find your clinic — AfriChart')
@section('description', 'Enter your clinic name to reach its AfriChart sign-in page.')

@section('content')
    {{--
        T2.2. On the guest/auth layout rather than the marketing one, per the
        page inventory: every Tier-2 entry page shares the calm centred card, and
        this is an auth entry point that happens to live on the root domain.
        Same tokens, same field idiom, same head partial as the marketing site.
    --}}
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6">
            <h1 class="text-xl font-medium text-ink tracking-tight">Find your clinic</h1>
            <p class="text-sm text-muted mt-2 leading-relaxed">
                Every clinic signs in at its own address. Enter your clinic's name and
                we'll take you there.
            </p>
        </div>

        <form method="POST" action="{{ route('find-clinic') }}" class="space-y-5"
            x-data="{
                loading: false,
                clinic: @js(old('clinic') ?? ''),
                get slug() {
                    return this.clinic
                        .toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .slice(0, 40)
                },
            }"
            @submit="loading = true">
            @csrf

            <div>
                <label for="clinic" class="block text-sm font-medium text-ink-body mb-2">Clinic name</label>
                <input id="clinic" name="clinic" type="text" x-model="clinic"
                    value="{{ old('clinic') }}" autofocus required autocomplete="organization"
                    placeholder="Grace Medical Centre"
                    class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent
                        focus:bg-page focus:border-ink focus:outline-none transition-colors">

                {{-- Live preview, the same device the sign-up form uses — it
                     teaches the subdomain model while you type. --}}
                <p class="mt-2 text-xs text-muted" aria-live="polite">
                    <span class="font-mono text-ink-body"
                        x-text="(slug || 'yourclinic') + '.{{ config('tenancy.root_domain') }}'">yourclinic.{{ config('tenancy.root_domain') }}</span>
                </p>

                @error('clinic')
                    <p class="mt-3 text-sm text-accent leading-relaxed">{{ $message }}</p>
                @enderror
            </div>

            <x-submit-button loadingText="Looking…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Go to my clinic
            </x-submit-button>
        </form>

        <div class="mt-6 pt-5 border-t border-line flex flex-col gap-2 text-center">
            <p class="text-sm text-muted">
                Don't know the name your clinic registered under? Ask your clinic administrator.
            </p>
            <p class="text-sm text-muted">
                New clinic?
                <a href="{{ route('signup') }}" class="text-ink font-medium hover:underline">Get started &rarr;</a>
            </p>
        </div>
    </div>
@endsection
