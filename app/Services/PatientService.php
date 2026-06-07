<?php

namespace App\Services;

use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientService extends BaseService
{
    public function __construct(
        protected PatientRepository $patientRepository
    ) {
        parent::__construct($patientRepository);
    }

    /**
     * Get paginated patient list with search/filter.
     */
    public function getPatientList(
        ?string $search = null,
        ?string $bloodGroup = null
    ): LengthAwarePaginator {
        return $this->patientRepository->getPaginated($search, $bloodGroup);
    }

    /**
     * Create a new patient with an auto-generated ID.
     */
    public function createPatient(array $data, int $registeredBy): Patient
    {
        $data['patient_id'] = $this->generatePatientId();
        $data['registered_by'] = $registeredBy;

        return $this->patientRepository->create($data);
    }

    /**
     * Update an existing patient.
     */
    public function updatePatient(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient->fresh();
    }

    /**
     * Generate a unique patient ID: ACH-YYYYMMDD-XXXX
     */
    private function generatePatientId(): string
    {
        $today = now()->format('Ymd');
        $prefix = "ACH-{$today}-";

        $todayCount = $this->patientRepository->countByPatientIdPrefix($prefix);
        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return $prefix.$sequence;
    }
}
