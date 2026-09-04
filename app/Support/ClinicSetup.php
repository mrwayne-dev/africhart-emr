<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffInvitation;

/**
 * What first-run setup still has left to do.
 *
 * The wizard COMPLETES a clinic; it does not create one. `tenant:create` has
 * already made the registry row and database, seeded a starter drug catalogue,
 * written whichever settings the operator passed on the command line, and
 * issued the first admin's invitation. Measured against a minimally-provisioned
 * clinic, what it leaves behind is:
 *
 *   done    10 medications, clinic_email, the first admin invite
 *   undone  address, phone, consultation fee, timezone, logo, the rest of staff
 *
 * So the wizard asks only for the gaps. It never re-asks the clinic name (set
 * at provisioning and central anyway), never re-issues the first admin's
 * invitation — the person walking the wizard IS that admin — and never offers
 * to "create" a drug list that already has ten entries in it.
 */
final class ClinicSetup
{
    public static function isComplete(): bool
    {
        return Setting::get(Setting::SETUP_COMPLETED_AT) !== null;
    }

    /**
     * Should this clinic be sent to the wizard on sign-in?
     *
     * Only a fresh one. A clinic with patients is in use, and dropping its
     * admin into a setup wizard would be both wrong and alarming — which is
     * what would happen to every EXISTING clinic the day this ships, since none
     * of them carries the marker.
     */
    public static function shouldPrompt(): bool
    {
        if (self::isComplete()) {
            return false;
        }

        return Patient::count() === 0;
    }

    public static function markComplete(): void
    {
        Setting::put(Setting::SETUP_COMPLETED_AT, now()->toDateTimeString());
    }

    /**
     * Per-step completion, for the progress indicator.
     *
     * @return array<string, bool>
     */
    public static function progress(): array
    {
        return [
            'profile' => (bool) Setting::get(Setting::CLINIC_ADDRESS)
                && (bool) Setting::get(Setting::CONSULTATION_FEE),
            'catalogue' => Medication::where('is_active', true)->exists(),
            'team' => Staff::count() > 1 || StaffInvitation::pending()->count() > 1,
        ];
    }
}
