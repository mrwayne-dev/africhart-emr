<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\Consultation;
use App\Models\PatientQueue;
use App\Repositories\ConsultationRepository;
use App\Repositories\PatientQueueRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultationService extends BaseService
{
    public function __construct(
        protected ConsultationRepository $consultationRepository,
        protected PatientQueueService $patientQueueService,
        protected PatientQueueRepository $patientQueueRepository,
    ) {
        parent::__construct($consultationRepository);
    }

    public function getConsultationList(
        ?string $search = null,
        ?string $status = null,
        ?int $doctorId = null
    ): LengthAwarePaginator {
        return $this->consultationRepository->getPaginated($search, $status, $doctorId);
    }

    /**
     * Start a new consultation for a patient.
     */
    public function startConsultation(array $data, int $doctorId): Consultation
    {
        $data['consultation_id'] = $this->generateConsultationId();
        $data['doctor_id'] = $doctorId;
        $data['status'] = ConsultationStatus::InProgress;

        // Absorb any vitals the nurse took while the patient was waiting, unless
        // the consultation already carries that field.
        $entry = $this->patientQueueRepository->findTodayByPatient($data['patient_id']);
        if ($entry) {
            foreach (PatientQueue::VITALS_FIELDS as $field) {
                if (! isset($data[$field]) && $entry->{$field} !== null) {
                    $data[$field] = $entry->{$field};
                }
            }
        }

        $consultation = $this->consultationRepository->create($data);

        // If the patient was queued, reflect that they're now being seen.
        $this->patientQueueService->markInConsultation($consultation->patient_id);

        return $consultation;
    }

    public function updateConsultation(Consultation $consultation, array $data): Consultation
    {
        $consultation->update($data);

        return $consultation->fresh();
    }

    public function recordVitals(Consultation $consultation, array $vitals): Consultation
    {
        $consultation->update($vitals);

        return $consultation->fresh();
    }

    public function completeConsultation(Consultation $consultation): Consultation
    {
        $consultation->update(['status' => ConsultationStatus::Completed]);

        $this->patientQueueService->markCompleted($consultation->patient_id);

        return $consultation->fresh();
    }

    /**
     * Generate a unique consultation ID: ACH-C-YYYYMMDD-XXXX
     */
    private function generateConsultationId(): string
    {
        $today = now()->format('Ymd');
        $prefix = "ACH-C-{$today}-";

        $todayCount = $this->consultationRepository->countByConsultationIdPrefix($prefix);
        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return $prefix.$sequence;
    }
}
