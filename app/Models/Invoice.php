<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'consultation_id',
    'created_by',
    'invoice_number',
    'subtotal',
    'tax',
    'discount',
    'total',
    'status',
    'payment_method',
    'paid_at',
    'notes',
])]
class Invoice extends Model
{
    use HasAuditTrail;

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // --- Accessors ---

    public function getIsPaidAttribute(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    // --- Audit ---

    /**
     * Name the event, not the table operation.
     *
     * Issuing an invoice and taking payment for it both reach the database as
     * `$invoice->update(['status' => ...])`, so both logged the identical line
     * "Updated invoice X". Three of those in a row told an auditor nothing —
     * least of all which one was money changing hands, which is the entry the
     * audit trail most needs to be able to produce.
     *
     * wasChanged() is available here because the trait fires on `updated`, after
     * Eloquent has populated $changes in finishSave().
     */
    public function auditDescription(string $action): string
    {
        if ($action === 'created') {
            return "Created invoice {$this->invoice_number}";
        }

        if ($action === 'deleted') {
            return "Deleted invoice {$this->invoice_number}";
        }

        if ($action === 'updated' && $this->wasChanged('status')) {
            $status = $this->status instanceof InvoiceStatus ? $this->status : InvoiceStatus::tryFrom((string) $this->status);

            return match ($status) {
                InvoiceStatus::Issued => "Issued invoice {$this->invoice_number} for {$this->formattedTotal()}",
                InvoiceStatus::Paid => "Marked invoice {$this->invoice_number} PAID — {$this->formattedTotal()}"
                    .($this->payment_method ? ' by '.$this->paymentMethodLabel() : ''),
                InvoiceStatus::PartiallyPaid => "Recorded a part payment on invoice {$this->invoice_number}",
                InvoiceStatus::Cancelled => "Cancelled invoice {$this->invoice_number}",
                default => "Invoice {$this->invoice_number} status changed to "
                    .($status?->value ?? (string) $this->status),
            };
        }

        if ($action === 'updated') {
            return "Updated invoice {$this->invoice_number}";
        }

        return "{$action} invoice {$this->invoice_number}";
    }

    /** Money as a person reads it, for the audit line. */
    private function formattedTotal(): string
    {
        return '₦'.number_format((float) $this->total, 2);
    }

    /**
     * payment_method is cast to the PaymentMethod enum, so it cannot be dropped
     * into a string. Interpolating it directly throws, and it would throw from
     * inside the audit write — which happens during markAsPaid(), so taking
     * payment would have failed outright rather than merely logging badly.
     */
    private function paymentMethodLabel(): string
    {
        $method = $this->payment_method;

        if ($method instanceof PaymentMethod) {
            return method_exists($method, 'label') ? $method->label() : $method->value;
        }

        return (string) $method;
    }
}
