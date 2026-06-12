@extends('layouts.app')

@section('title', 'Front Desk — AfriChart EMR')
@section('page-title', 'Front Desk')
@section('page-subtitle', 'Check-ins, queue and billing')

@section('content')
    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <x-stat-card title="Checked In Today" :value="$stats['checked_in_today']" icon="phosphor-sign-in" />
        <x-stat-card title="Pending Invoices" :value="$stats['pending_invoices']" icon="phosphor-receipt" />
        <x-stat-card title="Payments Today" :value="'₦' . number_format($stats['payments_today'], 2)" icon="phosphor-money" />
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

    {{-- Billing worklist: completed consultations awaiting an invoice (live) --}}
    <div class="mt-8"
        x-data="livePoll({ url: '{{ route('billing.ready.live') }}', interval: 12000, label: 'A consultation is ready to invoice', hash: '{{ $readyLiveHash }}' })">
        <div x-ref="region">
            @include('billing.partials.ready-to-invoice', ['consultations' => $readyToInvoice])
        </div>
    </div>

    <x-check-in-modal :patients="$patients" :doctors="$doctors" />
@endsection
