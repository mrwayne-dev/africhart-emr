@extends('layouts.app')

@section('title', 'Patient Queue — AfriChart EMR')
@section('page-title', 'Patient Queue')
@section('page-subtitle', "Today's waiting list · " . now()->format('l, j F Y'))

@php
    $user = auth()->user();
    $canCheckIn = $user->isAdmin() || $user->isNurse() || $user->isReceptionist();
@endphp

@section('content')
    <div class="bg-page border border-line rounded-card"
        x-data="livePoll({ url: '{{ route('queue.live') }}', interval: 8000, label: 'Queue updated', hash: '{{ $liveHash }}', meta: { count: {{ $queue->count() }} } })">
        <div class="px-6 py-4 border-b border-line flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-medium text-ink tracking-tight flex items-center gap-3">
                <span>
                    Today's Queue
                    <span class="ml-1 text-sm text-muted font-normal"><span x-text="meta.count"></span> {{ Str::plural('patient', $queue->count()) }}</span>
                </span>
                <x-live-indicator />
            </h2>
            @if ($canCheckIn)
                <button type="button" @click="$dispatch('open-modal', 'check-in')"
                    class="inline-flex items-center justify-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors shrink-0 w-full sm:w-auto">
                    <x-phosphor-plus class="w-4 h-4" />
                    Check In Patient
                </button>
            @endif
        </div>

        <div x-ref="region">
            @include('queue.partials.list')
        </div>
    </div>

    @if ($canCheckIn)
        <x-check-in-modal :patients="$patients" :doctors="$doctors" />
    @endif
@endsection
