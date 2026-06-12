@extends('layouts.app')

@section('title', 'Consultations — AfriChart EMR')
@section('page-title', 'Consultations')
@section('page-subtitle', 'Clinical notes and visits')

@php $user = auth()->user(); @endphp

@section('content')
    {{-- Filters --}}
    <form method="GET" action="{{ route('consultations.index') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by ID, patient or diagnosis…"
            class="flex-1 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
        <select name="status"
            class="sm:w-48 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            <option value="">All statuses</option>
            @foreach (\App\Enums\ConsultationStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(request('status') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        @if ($user->isDoctor())
            <label class="inline-flex items-center gap-2 text-sm text-muted px-2">
                <input type="checkbox" name="mine" value="1" @checked(request('mine')) class="rounded border-line">
                Mine only
            </label>
        @endif
        <button type="submit" class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">
            Search
        </button>
    </form>

    <div x-data="livePoll({ url: '{{ route('consultations.live').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}', interval: 10000, hash: '{{ $liveHash }}' })">
        <div class="bg-page border border-line rounded-card">
            <div class="px-6 py-4 border-b border-line flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-medium text-ink tracking-tight flex items-center gap-3">
                    <span>{{ $consultations->total() }} {{ Str::plural('consultation', $consultations->total()) }}</span>
                    <x-live-indicator />
                </h2>
                @can('create', \App\Models\Consultation::class)
                    <a href="{{ route('consultations.create') }}"
                        class="inline-flex items-center justify-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors shrink-0 w-full sm:w-auto">
                        <x-phosphor-plus class="w-4 h-4" />
                        New Consultation
                    </a>
                @endcan
            </div>
            <div x-ref="region">
                @include('consultations.partials.list')
            </div>
        </div>
    </div>
@endsection
