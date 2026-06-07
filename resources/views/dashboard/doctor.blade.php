@extends('layouts.app')

@section('title', 'Doctor Dashboard — AfriChart EMR')
@section('page-title', 'Doctor Dashboard')
@section('page-subtitle', 'Recently registered patients')

@section('content')
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
