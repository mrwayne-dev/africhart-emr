<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Prescription;
use App\Repositories\PrescriptionRepository;

class PrescriptionService extends BaseService
{
    public function __construct(
        protected PrescriptionRepository $prescriptionRepository
    ) {
        parent::__construct($prescriptionRepository);
    }

    /**
     * Add a prescription to a consultation, inheriting its patient + prescriber.
     */
    public function addToConsultation(Consultation $consultation, array $data, int $prescribedBy): Prescription
    {
        $data['consultation_id'] = $consultation->id;
        $data['patient_id'] = $consultation->patient_id;
        $data['prescribed_by'] = $prescribedBy;

        return $this->prescriptionRepository->create($data);
    }

    public function remove(Prescription $prescription): bool
    {
        return (bool) $prescription->delete();
    }
}
