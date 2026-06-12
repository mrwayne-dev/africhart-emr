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
        return $this->belongsTo(User::class, 'created_by');
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

    public function auditDescription(string $action): string
    {
        return match ($action) {
            'created' => "Created invoice {$this->invoice_number}",
            'updated' => "Updated invoice {$this->invoice_number}",
            'deleted' => "Deleted invoice {$this->invoice_number}",
            default => "{$action} invoice {$this->invoice_number}",
        };
    }
}
