<?php

namespace App\Enums;

enum ConsultationStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::FollowUp => 'Follow-up',
        };
    }

    /**
     * Tailwind classes for a status badge pill.
     */
    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-700',
            self::Completed => 'bg-emerald-100 text-emerald-700',
            self::FollowUp => 'bg-amber-100 text-amber-700',
        };
    }
}
