<?php

namespace App\Enums;

enum MedicationRoute: string
{
    case Oral = 'oral';
    case IV = 'iv';
    case IM = 'im';
    case Topical = 'topical';
    case Sublingual = 'sublingual';
    case Rectal = 'rectal';
    case Inhaled = 'inhaled';

    public function label(): string
    {
        return match ($this) {
            self::Oral => 'Oral',
            self::IV => 'Intravenous (IV)',
            self::IM => 'Intramuscular (IM)',
            self::Topical => 'Topical',
            self::Sublingual => 'Sublingual',
            self::Rectal => 'Rectal',
            self::Inhaled => 'Inhaled',
        };
    }
}
