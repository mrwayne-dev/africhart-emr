<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    /**
     * Admins, doctors and nurses can browse consultations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isDoctor() || $user->isNurse();
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $user->isAdmin() || $user->isDoctor() || $user->isNurse();
    }

    /**
     * Only doctors and admins can start a consultation.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDoctor();
    }

    /**
     * Only the doctor who created it, or an admin, may edit the notes.
     */
    public function update(User $user, Consultation $consultation): bool
    {
        return $user->isAdmin() || $user->id === $consultation->doctor_id;
    }

    /**
     * Vitals may be recorded by the owning doctor, any nurse, or an admin.
     */
    public function recordVitals(User $user, Consultation $consultation): bool
    {
        return $user->isAdmin()
            || $user->isNurse()
            || $user->id === $consultation->doctor_id;
    }

    /**
     * Completing a consultation is restricted to its doctor or an admin.
     */
    public function complete(User $user, Consultation $consultation): bool
    {
        return $user->isAdmin() || $user->id === $consultation->doctor_id;
    }
}
