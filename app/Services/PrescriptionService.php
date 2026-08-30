<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Medication;
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

        /*
         * Keep the catalogue link and the written name in step.
         *
         * When the doctor picked from the catalogue, the stored name is the
         * catalogue's, not whatever the form happened to submit — otherwise the
         * two could disagree from the moment the row is written, which is the
         * desynchronisation the link exists to prevent.
         *
         * When nothing was picked, an exact name match is adopted so a drug
         * typed rather than selected still links up. No match is fine: that is
         * an off-catalogue prescription, which stays free text.
         */
        if (! empty($data['medication_id'])) {
            $medication = Medication::find($data['medication_id']);

            if ($medication) {
                $data['medication_name'] = $medication->name;
            } else {
                // The id did not resolve; do not store a dangling reference.
                $data['medication_id'] = null;
            }
        } elseif (! empty($data['medication_name'])) {
            $data['medication_id'] = Medication::where('name', $data['medication_name'])->value('id');
        }

        return $this->prescriptionRepository->create($data);
    }

    public function remove(Prescription $prescription): bool
    {
        return (bool) $prescription->delete();
    }
}
