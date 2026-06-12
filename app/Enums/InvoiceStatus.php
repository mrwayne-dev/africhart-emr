<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Paid => 'Paid',
            self::PartiallyPaid => 'Partially Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Tailwind classes for a status badge pill.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-600',
            self::Issued => 'bg-blue-100 text-blue-700',
            self::Paid => 'bg-emerald-100 text-emerald-700',
            self::PartiallyPaid => 'bg-amber-100 text-amber-700',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }
}
