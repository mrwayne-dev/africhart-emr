@extends('layouts.app')

@section('title', 'Invoices — AfriChart EMR')
@section('page-title', 'Invoices')
@section('page-subtitle', 'Billing and payments')

@section('content')
    {{-- Filters --}}
    <form method="GET" action="{{ route('invoices.index') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by invoice no. or patient…"
            class="flex-1 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
        <select name="status"
            class="sm:w-48 bg-warm rounded text-sm text-ink-body px-4 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
            <option value="">All statuses</option>
            @foreach (\App\Enums\InvoiceStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(request('status') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90 transition-colors">Search</button>
    </form>

    <div class="bg-page border border-line rounded-card">
        <div class="px-6 py-4 border-b border-line">
            <h2 class="text-base font-medium text-ink tracking-tight">
                {{ $invoices->total() }} {{ Str::plural('invoice', $invoices->total()) }}
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                        <th class="px-4 py-3 font-medium">Invoice No.</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="border-b border-line last:border-0 even:bg-warm/60 hover:bg-warm transition-colors cursor-pointer"
                            onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                            <td class="px-4 py-3 font-medium text-ink whitespace-nowrap">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-ink">{{ $invoice->patient?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink">₦{{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $invoice->status->color() }}">
                                    {{ $invoice->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $invoice->created_at->format('j M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-muted">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($invoices->hasPages())
        <div class="mt-6">{{ $invoices->links() }}</div>
    @endif
@endsection
