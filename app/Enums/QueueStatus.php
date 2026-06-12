<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case InConsultation = 'in_consultation';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::InConsultation => 'In Consultation',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Tailwind classes for a status badge pill.
     */
    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'bg-amber-100 text-amber-700',
            self::InConsultation => 'bg-blue-100 text-blue-700',
            self::Completed => 'bg-emerald-100 text-emerald-700',
            self::Cancelled => 'bg-slate-100 text-slate-600',
        };
    }
}
