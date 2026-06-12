@extends('layouts.app')

@section('title', 'Admin Dashboard — AfriChart EMR')
@section('page-title', 'Admin Dashboard')
@section('page-subtitle', 'Overview of clinic activity')

@section('content')
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
        <x-stat-card title="Total Patients" :value="$stats['total_patients']" icon="phosphor-users" />
        <x-stat-card title="Registered Today" :value="$stats['today_registered']" icon="phosphor-user-plus" />
        <x-stat-card title="This Week" :value="$stats['this_week']" icon="phosphor-calendar-blank" />
        <x-stat-card title="Consultations Today" :value="$stats['today_consultations']" icon="phosphor-stethoscope" />
        <x-stat-card title="Pending Invoices" :value="$stats['pending_invoices']" icon="phosphor-receipt" />
        <x-stat-card title="Revenue This Month" :value="'₦' . number_format($stats['revenue_this_month'], 2)" icon="phosphor-money" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
        <div class="lg:col-span-2">
            <x-chart id="registrationsChart" type="line" label="Patient Registrations (Last 30 Days)"
                :labels="array_keys($registrationTrend)" :data="array_values($registrationTrend)" />
        </div>
        <x-chart id="revenueChart" type="bar" label="Revenue (Last 6 Months)"
            :labels="array_keys($revenueTrend)" :data="array_values($revenueTrend)" />
        <x-chart id="consultationsChart" type="doughnut" label="Consultations by Status"
            :labels="array_keys($consultationBreakdown)" :data="array_values($consultationBreakdown)" />
    </div>

    {{-- Quick exports --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <span class="text-sm text-muted mr-1">Export:</span>
        <a href="{{ route('export.patients') }}" class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-3.5 py-1.5 text-sm font-medium hover:bg-warm transition-colors">
            <x-phosphor-download-simple class="w-4 h-4" /> Patients
        </a>
        <a href="{{ route('export.consultations') }}" class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-3.5 py-1.5 text-sm font-medium hover:bg-warm transition-colors">
            <x-phosphor-download-simple class="w-4 h-4" /> Consultations
        </a>
        <a href="{{ route('export.invoices') }}" class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-3.5 py-1.5 text-sm font-medium hover:bg-warm transition-colors">
            <x-phosphor-download-simple class="w-4 h-4" /> Invoices
        </a>
    </div>

    {{-- Billing worklist: completed consultations awaiting an invoice (live) --}}
    <div class="mb-8"
        x-data="livePoll({ url: '{{ route('billing.ready.live') }}', interval: 12000, label: 'A consultation is ready to invoice', hash: '{{ $readyLiveHash }}' })">
        <div x-ref="region">
            @include('billing.partials.ready-to-invoice', ['consultations' => $readyToInvoice])
        </div>
    </div>

    {{-- Activity feed --}}
    <div class="bg-page border border-line rounded-card">
        <div class="px-6 py-4 border-b border-line flex items-center justify-between">
            <h2 class="text-base font-medium text-ink tracking-tight">Recent Activity</h2>
            <a href="{{ route('audit.index') }}" class="text-sm text-muted hover:text-ink">View audit log →</a>
        </div>
        <div class="divide-y divide-line">
            @forelse ($activityFeed as $entry)
                <div class="px-6 py-3 flex items-center justify-between gap-4">
                    <p class="text-sm text-ink-body">{{ $entry->description }}</p>
                    <span class="text-xs text-muted whitespace-nowrap shrink-0">
                        {{ $entry->user_name }} · {{ $entry->created_at?->diffForHumans() }}
                    </span>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-muted">No activity recorded yet.</p>
            @endforelse
        </div>
    </div>
@endsection
