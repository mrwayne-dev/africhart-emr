<?php

namespace App\Enums;

/**
 * A clinic staff member's role.
 *
 * Renamed from StaffRole with the table and model (D5) — these are roles WITHIN
 * a clinic. Platform operators are not on this scale at all; they authenticate
 * against central platform_admins through a separate guard.
 */
enum StaffRole: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Nurse = 'nurse';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Doctor => 'Doctor',
            self::Nurse => 'Nurse',
            self::Receptionist => 'Receptionist',
        };
    }
}
