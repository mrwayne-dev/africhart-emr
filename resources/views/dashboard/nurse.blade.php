@extends('layouts.app')

@section('title', 'Nurse Dashboard — AfriChart EMR')
@section('page-title', 'Nurse Dashboard')
@section('page-subtitle', "Today's queue and vitals")

@section('content')
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <x-stat-card title="Patients Waiting" :value="$stats['waiting']" icon="phosphor-hourglass" />
        <x-stat-card title="In Consultation" :value="$stats['in_consultation']" icon="phosphor-stethoscope" />
        <x-stat-card title="Completed Today" :value="$stats['completed_today']" icon="phosphor-check-circle" />
    </div>

    {{-- Today's queue (live) --}}
    <div class="bg-page border border-line rounded-card"
        x-data="livePoll({ url: '{{ route('queue.live') }}', interval: 8000, label: 'Queue updated', hash: '{{ $queueLiveHash }}' })">
        <div class="px-6 py-4 border-b border-line flex items-center justify-between">
            <h2 class="text-base font-medium text-ink tracking-tight flex items-center gap-3">
                Today's Queue <x-live-indicator />
            </h2>
            <button type="button" @click="$dispatch('open-modal', 'check-in')"
                class="inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors">
                <x-phosphor-plus class="w-4 h-4" />
                Check In Patient
            </button>
        </div>
        <div x-ref="region">
            @include('queue.partials.list')
        </div>
    </div>

    <x-check-in-modal :patients="$patients" :doctors="$doctors" />
@endsection
