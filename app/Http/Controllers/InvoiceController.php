<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Http\Requests\MarkInvoicePaidRequest;
use App\Http\Requests\StoreInvoiceItemRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\AdminNotifier;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends BaseController
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected AdminNotifier $adminNotifier,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->invoiceService->getInvoiceList(
            search: $request->input('search'),
            status: $request->input('status'),
        );

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'consultation', 'createdBy', 'items']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Generate (or jump to the existing) invoice for a consultation.
     */
    public function generateFromConsultation(Consultation $consultation): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        if ($consultation->invoice) {
            return redirect()
                ->route('invoices.show', $consultation->invoice)
                ->with('success', 'This consultation already has an invoice.');
        }

        if ($consultation->status !== ConsultationStatus::Completed) {
            return back()->with('error', 'An invoice can only be generated once the consultation is completed.');
        }

        $invoice = $this->invoiceService->generateFromConsultation($consultation, request()->user()->id);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice generated — '.$invoice->invoice_number);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice->update($request->validated());
        $this->invoiceService->recalculateTotals($invoice);

        return back()->with('success', 'Invoice updated.');
    }

    public function addItem(StoreInvoiceItemRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->invoiceService->addItem($invoice, $request->validated());
        $this->invoiceService->recalculateTotals($invoice);

        return back()->with('success', 'Line item added.');
    }

    public function updateItem(StoreInvoiceItemRequest $request, InvoiceItem $item): RedirectResponse
    {
        $this->authorize('update', $item->invoice);

        $this->invoiceService->updateItem($item, $request->validated());

        return back()->with('success', 'Line item updated.');
    }

    public function removeItem(InvoiceItem $item): RedirectResponse
    {
        $this->authorize('update', $item->invoice);

        $this->invoiceService->removeItem($item);

        return back()->with('success', 'Line item removed.');
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->invoiceService->issue($invoice);
        $this->adminNotifier->invoiceIssued($invoice, request()->user());

        return back()->with('success', 'Invoice issued.');
    }

    public function markPaid(MarkInvoicePaidRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('markPaid', $invoice);

        $this->invoiceService->markAsPaid($invoice, $request->validated()['payment_method']);

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'consultation', 'createdBy', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
