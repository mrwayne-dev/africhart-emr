@extends('layouts.app')

@section('title', 'Admin Dashboard — AfriChart EMR')
@section('page-title', 'Admin Dashboard')
@section('page-subtitle', 'Overview of clinic activity')

@section('content')
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <x-stat-card title="Total Patients" :value="$stats['total_patients']" icon="phosphor-users" />
        <x-stat-card title="Registered Today" :value="$stats['today_registered']" icon="phosphor-user-plus" />
        <x-stat-card title="This Week" :value="$stats['this_week']" icon="phosphor-calendar-blank" />
    </div>

    {{-- Recent patients --}}
    <div class="bg-page border border-line rounded-card">
        <div class="px-6 py-4 border-b border-line flex items-center justify-between">
            <h2 class="text-base font-medium text-ink tracking-tight">Recent Registrations</h2>
            <a href="{{ route('patients.create') }}"
                class="inline-flex items-center gap-1.5 bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors">
                <x-phosphor-plus class="w-4 h-4" />
                Register Patient
            </a>
        </div>
        <x-patient-table :patients="$recentPatients" />
    </div>
@endsection
