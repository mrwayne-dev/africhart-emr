<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'description',
    'unit_price',
    'quantity',
    'amount',
    'category',
])]
class InvoiceItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    // --- Relationships ---

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
