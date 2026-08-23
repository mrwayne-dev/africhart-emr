<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\Staff;

class ConsultationPolicy
{
    /**
     * Admins, doctors and nurses can browse consultations.
     */
    public function viewAny(Staff $user): bool
    {
        return $user->isAdmin() || $user->isDoctor() || $user->isNurse();
    }

    public function view(Staff $user, Consultation $consultation): bool
    {
        return $user->isAdmin() || $user->isDoctor() || $user->isNurse();
    }

    /**
     * Only doctors and admins can start a consultation.
     */
    public function create(Staff $user): bool
    {
        return $user->isAdmin() || $user->isDoctor();
    }

    /**
     * Only the doctor who created it, or an admin, may edit the notes.
     */
    public function update(Staff $user, Consultation $consultation): bool
    {
        return $user->isAdmin() || $user->id === $consultation->doctor_id;
    }

    /**
     * Vitals may be recorded by the owning doctor, any nurse, or an admin.
     */
    public function recordVitals(Staff $user, Consultation $consultation): bool
    {
        return $user->isAdmin()
            || $user->isNurse()
            || $user->id === $consultation->doctor_id;
    }

    /**
     * Completing a consultation is restricted to its doctor or an admin.
     */
    public function complete(Staff $user, Consultation $consultation): bool
    {
        return $user->isAdmin() || $user->id === $consultation->doctor_id;
    }
}
