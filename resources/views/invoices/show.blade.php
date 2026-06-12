@extends('layouts.app')

@section('title', 'Invoice '.$invoice->invoice_number.' — AfriChart EMR')
@section('page-title', 'Invoice')
@section('page-subtitle', $invoice->invoice_number)

@php
    $canManage = auth()->user()->can('update', $invoice);
    $isPaid = $invoice->status === \App\Enums\InvoiceStatus::Paid;
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6 no-print">
        <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink">
            <x-phosphor-arrow-left class="w-4 h-4" /> Back to invoices
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('invoices.pdf', $invoice) }}"
                class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-4 py-2 text-sm font-medium hover:bg-warm transition-colors">
                <x-phosphor-download-simple class="w-4 h-4" /> PDF
            </a>
            <x-print-button />
        </div>
    </div>

    <div class="bg-page border border-line rounded-card max-w-3xl print-clean">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-line flex items-start justify-between">
            <div>
                <h1 class="text-lg font-medium text-ink tracking-tight">{{ $invoice->invoice_number }}</h1>
                <p class="text-sm text-muted mt-1">{{ $invoice->created_at->format('j F Y') }}</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $invoice->status->color() }}">
                {{ $invoice->status->label() }}
            </span>
        </div>

        {{-- Patient / consultation --}}
        <div class="px-6 py-4 border-b border-line grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-muted">Patient</p>
                <p class="text-ink font-medium">{{ $invoice->patient?->full_name ?? '—' }}</p>
                <p class="text-muted">{{ $invoice->patient?->patient_id }}</p>
            </div>
            @if ($invoice->consultation)
                <div>
                    <p class="text-muted">Consultation</p>
                    <a href="{{ route('consultations.show', $invoice->consultation) }}" class="text-ink font-medium hover:underline">{{ $invoice->consultation->consultation_id }}</a>
                </div>
            @endif
        </div>

        {{-- Line items --}}
        <div class="px-6 py-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted border-b border-line whitespace-nowrap">
                            <th class="py-2 font-medium">Description</th>
                            <th class="py-2 font-medium text-right">Unit Price</th>
                            <th class="py-2 font-medium text-right w-16">Qty</th>
                            <th class="py-2 font-medium text-right">Amount</th>
                            @if ($canManage && ! $isPaid)<th class="py-2 w-8"></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr class="border-b border-line">
                                @if ($canManage && ! $isPaid)
                                    {{-- Inline-editable row --}}
                                    <td colspan="{{ 5 }}" class="py-2">
                                        <form method="POST" action="{{ route('invoices.items.update', $item) }}"
                                            class="grid grid-cols-12 gap-2 items-center">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="description" value="{{ $item->description }}" required
                                                class="col-span-6 bg-warm rounded text-sm px-3 py-1.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                                            <input type="number" step="0.01" min="0" name="unit_price" value="{{ $item->unit_price }}" required
                                                class="col-span-2 bg-warm rounded text-sm px-2 py-1.5 text-right border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                                            <input type="number" min="1" name="quantity" value="{{ $item->quantity }}" required
                                                class="col-span-1 bg-warm rounded text-sm px-2 py-1.5 text-right border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                                            <span class="col-span-2 text-right text-ink">₦{{ number_format((float) $item->amount, 2) }}</span>
                                            <div class="col-span-1 flex items-center justify-end gap-1">
                                                <button type="submit" class="text-muted hover:text-ink" title="Save"><x-phosphor-check class="w-4 h-4" /></button>
                                            </div>
                                        </form>
                                    </td>
                                @else
                                    <td class="py-2 text-ink-body">{{ $item->description }}</td>
                                    <td class="py-2 text-right text-muted">₦{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="py-2 text-right text-muted">{{ $item->quantity }}</td>
                                    <td class="py-2 text-right text-ink">₦{{ number_format((float) $item->amount, 2) }}</td>
                                @endif
                            </tr>
                            @if ($canManage && ! $isPaid)
                                <tr>
                                    <td colspan="5" class="pb-2 text-right">
                                        <form method="POST" action="{{ route('invoices.items.destroy', $item) }}"
                                            onsubmit="return confirm('Remove this line item?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-muted hover:text-accent">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-muted">No line items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Add item --}}
            @if ($canManage && ! $isPaid)
                <form method="POST" action="{{ route('invoices.items.store', $invoice) }}"
                    class="grid grid-cols-12 gap-2 items-center mt-3 pt-3 border-t border-line">
                    @csrf
                    <input type="text" name="description" placeholder="Add line item…" required
                        class="col-span-6 bg-warm rounded text-sm px-3 py-1.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                    <input type="number" step="0.01" min="0" name="unit_price" placeholder="Price" required
                        class="col-span-2 bg-warm rounded text-sm px-2 py-1.5 text-right border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                    <input type="number" min="1" name="quantity" value="1" required
                        class="col-span-1 bg-warm rounded text-sm px-2 py-1.5 text-right border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                    <select name="category" class="col-span-2 bg-warm rounded text-sm px-2 py-1.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                        <option value="service">Service</option>
                        <option value="medication">Medication</option>
                        <option value="lab">Lab</option>
                        <option value="other">Other</option>
                    </select>
                    <button type="submit" class="col-span-1 text-ink hover:text-ink/70 flex justify-end" title="Add"><x-phosphor-plus class="w-4 h-4" /></button>
                </form>
            @endif
        </div>

        {{-- Totals --}}
        <div class="px-6 py-4 border-t border-line">
            <div class="ml-auto max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-muted">Subtotal</span><span class="text-ink">₦{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                @if ($canManage && ! $isPaid)
                    <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="space-y-1.5">
                        @csrf
                        @method('PUT')
                        <div class="flex justify-between items-center">
                            <span class="text-muted">Tax</span>
                            <input type="number" step="0.01" min="0" name="tax" value="{{ $invoice->tax }}"
                                class="w-28 bg-warm rounded text-sm px-2 py-1 text-right border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted">Discount</span>
                            <input type="number" step="0.01" min="0" name="discount" value="{{ $invoice->discount }}"
                                class="w-28 bg-warm rounded text-sm px-2 py-1 text-right border border-transparent focus:bg-page focus:border-ink focus:outline-none">
                        </div>
                        <div class="text-right">
                            <button type="submit" class="text-xs text-ink font-medium hover:underline">Update tax / discount</button>
                        </div>
                    </form>
                @else
                    <div class="flex justify-between"><span class="text-muted">Tax</span><span class="text-ink">₦{{ number_format((float) $invoice->tax, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Discount</span><span class="text-ink">₦{{ number_format((float) $invoice->discount, 2) }}</span></div>
                @endif
                <div class="flex justify-between border-t border-line pt-2 mt-2 text-base font-medium">
                    <span class="text-ink">Total</span><span class="text-ink">₦{{ number_format((float) $invoice->total, 2) }}</span>
                </div>
                @if ($isPaid && $invoice->payment_method)
                    <p class="text-xs text-muted text-right pt-1">Paid via {{ $invoice->payment_method->label() }} · {{ $invoice->paid_at?->format('j M Y, g:i A') }}</p>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        @can('update', $invoice)
            @unless ($isPaid)
                <div class="px-6 py-4 border-t border-line flex flex-wrap items-center justify-end gap-2">
                    @if ($invoice->status === \App\Enums\InvoiceStatus::Draft)
                        <form method="POST" action="{{ route('invoices.issue', $invoice) }}" x-data="{ loading: false }" @submit="loading = true">
                            @csrf
                            @method('PATCH')
                            <x-submit-button loadingText="Issuing…"
                                class="border border-line text-ink rounded-full px-4 py-2 text-sm font-medium hover:bg-warm">
                                Issue Invoice
                            </x-submit-button>
                        </form>
                    @endif
                    @can('markPaid', $invoice)
                        <button type="button" @click="$dispatch('open-modal', 'mark-paid')"
                            class="bg-ink text-white rounded-full px-4 py-2 text-sm font-medium hover:bg-ink/90 transition-colors">
                            Mark as Paid
                        </button>
                    @endcan
                </div>
            @endunless
        @endcan
    </div>

    {{-- Mark paid modal --}}
    @can('markPaid', $invoice)
        <x-modal name="mark-paid" title="Mark Invoice as Paid">
            <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="space-y-5"
                x-data="{ loading: false }" @submit="loading = true">
                @csrf
                @method('PATCH')
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-ink-body mb-2">Payment Method</label>
                    <select name="payment_method" id="payment_method" required
                        class="w-full bg-warm rounded text-sm text-ink-body px-4 py-3 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <option value="">Select…</option>
                        @foreach (\App\Enums\PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="mt-2 text-sm text-accent">{{ $message }}</p>@enderror
                </div>
                <p class="text-sm text-muted">Total due: <span class="text-ink font-medium">₦{{ number_format((float) $invoice->total, 2) }}</span></p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'mark-paid')" class="px-4 py-2.5 text-sm font-medium text-muted hover:text-ink">Cancel</button>
                    <x-submit-button loadingText="Saving…" class="bg-ink text-white rounded-full px-5 py-2.5 text-sm font-medium hover:bg-ink/90">
                        Confirm Payment
                    </x-submit-button>
                </div>
            </form>
        </x-modal>
    @endcan

    @if ($errors->has('payment_method'))
        <script>
            document.addEventListener('DOMContentLoaded', () =>
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'mark-paid' }))
            );
        </script>
    @endif
@endsection
