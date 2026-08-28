<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\Setting;
use App\Repositories\InvoiceRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvoiceService extends BaseService
{
    public function __construct(
        protected InvoiceRepository $invoiceRepository
    ) {
        parent::__construct($invoiceRepository);
    }

    public function getInvoiceList(?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return $this->invoiceRepository->getPaginated($search, $status);
    }

    /**
     * Generate a draft invoice from a completed consultation, pre-populated with
     * the consultation fee and one line per prescribed medication.
     */
    public function generateFromConsultation(Consultation $consultation, int $createdBy): Invoice
    {
        $invoice = $this->invoiceRepository->create([
            'patient_id' => $consultation->patient_id,
            'consultation_id' => $consultation->id,
            'created_by' => $createdBy,
            'invoice_number' => $this->generateInvoiceNumber(),
            'status' => InvoiceStatus::Draft,
        ]);

        // Consultation fee as the first item.
        $this->addItem($invoice, [
            'description' => 'Consultation Fee',
            'unit_price' => $this->consultationFee(),
            'quantity' => 1,
            'category' => 'service',
        ]);

        // One line per prescribed medication, pre-priced from the drug catalog
        // (still editable inline by the receptionist).
        $prices = $this->catalogPrices($consultation->prescriptions->pluck('medication_name'));
        foreach ($consultation->prescriptions as $prescription) {
            $qty = $prescription->quantity ?? 1;
            $this->addItem($invoice, [
                'description' => trim("{$prescription->medication_name} {$prescription->dosage}")." × {$qty}",
                'unit_price' => $prices[Str::lower($prescription->medication_name)] ?? 0,
                'quantity' => $qty,
                'category' => 'medication',
            ]);
        }

        $this->recalculateTotals($invoice);

        return $invoice->fresh(['items']);
    }

    /**
     * Build a lowercased name => default_price map for the given medication names.
     *
     * @param  Collection<int, string>  $names
     * @return array<string, float>
     */
    private function catalogPrices($names): array
    {
        $names = $names->filter()->unique()->values();

        if ($names->isEmpty()) {
            return [];
        }

        return Medication::whereIn('name', $names)
            ->pluck('default_price', 'name')
            ->mapWithKeys(fn ($price, $name) => [Str::lower($name) => (float) $price])
            ->all();
    }

    /**
     * Add a single line item and recalculate its amount.
     */
    public function addItem(Invoice $invoice, array $data): InvoiceItem
    {
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 1);

        return $invoice->items()->create([
            'description' => $data['description'],
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'amount' => $unitPrice * $quantity,
            'category' => $data['category'] ?? 'service',
        ]);
    }

    public function updateItem(InvoiceItem $item, array $data): InvoiceItem
    {
        $unitPrice = (float) ($data['unit_price'] ?? $item->unit_price);
        $quantity = (int) ($data['quantity'] ?? $item->quantity);

        $item->update([
            'description' => $data['description'] ?? $item->description,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'amount' => $unitPrice * $quantity,
            'category' => $data['category'] ?? $item->category,
        ]);

        $this->recalculateTotals($item->invoice);

        return $item->fresh();
    }

    public function removeItem(InvoiceItem $item): void
    {
        $invoice = $item->invoice;
        $item->delete();
        $this->recalculateTotals($invoice);
    }

    /**
     * Recalculate subtotal/total from line items + tax/discount.
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->items()->sum('amount');
        $total = $subtotal + (float) $invoice->tax - (float) $invoice->discount;

        $invoice->update([
            'subtotal' => $subtotal,
            'total' => max(0, $total),
        ]);
    }

    public function markAsPaid(Invoice $invoice, string $paymentMethod): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::Paid,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function issue(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::Issued]);

        return $invoice->fresh();
    }

    /**
     * This clinic's own consultation fee.
     *
     * Was config('billing.consultation_fee') — one value for the entire server,
     * so every clinic billed the same amount whatever it actually charges.
     *
     * The old config value survives as the FALLBACK, deliberately: a clinic
     * that has not set a fee yet must bill the platform default, never zero. A
     * null coalescing to 0.0 would put a free consultation on a real invoice.
     */
    public function consultationFee(): float
    {
        return (float) Setting::get(Setting::CONSULTATION_FEE, config('billing.consultation_fee'));
    }

    /**
     * Generate a unique invoice number: <CLINIC>-INV-YYYYMMDD-XXXX
     */
    private function generateInvoiceNumber(): string
    {
        $today = now()->format('Ymd');
        /*
         * The prefix is the CLINIC's, from the central registry — not the
         * hardcoded "ACH". Two clinics previously minted identical identifiers:
         * verified live, where Hope (7 patients) and Grace (3) both generated
         * ACH-{DATE}-0001, because the counter is scoped to each clinic's own
         * database while the prefix was scoped to nothing.
         *
         * It lives on `clinics` behind a UNIQUE index rather than in tenant
         * settings, because distinctness across clinics is the whole point and
         * nothing inside one clinic's database can enforce it.
         */
        $prefix = tenant()->idPrefix()."-INV-{$today}-";

        $todayCount = $this->invoiceRepository->countByInvoiceNumberPrefix($prefix);
        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return $prefix.$sequence;
    }
}
