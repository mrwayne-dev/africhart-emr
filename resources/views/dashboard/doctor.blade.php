@extends('layouts.app')

@section('title', 'Doctor Dashboard — AfriChart EMR')
@section('page-title', 'Doctor Dashboard')
@section('page-subtitle', 'Your queue and consultations')

@section('content')
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <x-stat-card title="Consultations Today" :value="$stats['consultations_today']" icon="phosphor-stethoscope" />
        <x-stat-card title="This Week" :value="$stats['consultations_week']" icon="phosphor-calendar-blank" />
        <x-stat-card title="Patients Seen (Total)" :value="$stats['patients_seen_total']" icon="phosphor-users" />
    </div>

    {{-- My queue (live) --}}
    <div class="bg-page border border-line rounded-card"
        x-data="livePoll({ url: '{{ route('queue.live') }}?mine=1', interval: 8000, label: 'Queue updated', hash: '{{ $queueLiveHash }}' })">
        <div class="px-6 py-4 border-b border-line">
            <h2 class="text-base font-medium text-ink tracking-tight flex items-center gap-3">
                My Queue Today <x-live-indicator />
            </h2>
        </div>
        <div x-ref="region">
            @include('queue.partials.list', ['doctors' => collect()])
        </div>
    </div>
@endsection
