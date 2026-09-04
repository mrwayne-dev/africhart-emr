@extends('layouts.app')

@section('title', 'Set up your clinic')
@section('page-title', 'Welcome')
@section('page-subtitle', 'Your drug prices')

@section('content')
<x-setup-shell :step="$step" :progress="$progress"
    title="Your drug prices"
    lead="We started you off with common medications. The names are right; the prices are placeholders — set yours now, or leave them and edit later in Settings.">

    <form method="POST" action="{{ route('setup.catalogue.store') }}"
        x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="overflow-x-auto -mx-6 sm:mx-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted border-b border-line">
                        <th class="px-6 sm:px-4 py-3 font-medium">Medication</th>
                        <th class="px-6 sm:px-4 py-3 font-medium w-40">Your price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($medications as $medication)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-6 sm:px-4 py-3">
                                <span class="text-ink font-medium">{{ $medication->name }}</span>
                                @if ($medication->common_frequency)
                                    <span class="text-muted"> · {{ $medication->common_frequency }}</span>
                                @endif
                            </td>
                            <td class="px-6 sm:px-4 py-2">
                                <label class="sr-only" for="price-{{ $medication->id }}">
                                    Price for {{ $medication->name }}
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted pointer-events-none">₦</span>
                                    <input type="number" step="0.01" min="0"
                                        id="price-{{ $medication->id }}"
                                        name="prices[{{ $medication->id }}]"
                                        value="{{ old('prices.'.$medication->id, $medication->default_price) }}"
                                        class="w-full bg-warm rounded text-sm text-ink-body pl-7 pr-3 py-2.5
                                            border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-10 text-center text-muted">
                                No medications yet — you can add them in Settings.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-3 pt-6">
            <x-submit-button loadingText="Saving…"
                class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                Continue
            </x-submit-button>
            <a href="{{ route('setup.team') }}" class="text-sm text-muted hover:text-ink transition-colors">
                Leave the prices as they are
            </a>
        </div>
    </form>
</x-setup-shell>
@endsection
