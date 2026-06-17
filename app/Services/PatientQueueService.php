<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\PatientQueue;
use App\Repositories\PatientQueueRepository;
use Illuminate\Database\Eloquent\Collection;

class PatientQueueService extends BaseService
{
    public function __construct(
        protected PatientQueueRepository $queueRepository
    ) {
        parent::__construct($queueRepository);
    }

    /**
     * Today's queue (optionally for one doctor).
     */
    public function getTodayQueue(?int $doctorId = null): Collection
    {
        return $this->queueRepository->getTodayQueue($doctorId);
    }

    /**
     * Cheap change-signal parts for live polling (single source of truth shared by
     * the queue page and the dashboards that embed the queue).
     *
     * @return array<int, string>
     */
    public function liveHashParts(Collection $queue): array
    {
        return $queue->map(fn ($e) => $e->id.'|'.$e->status->value.'|'.$e->assigned_doctor_id.'|'.$e->has_vitals.'|'.$e->updated_at)->all();
    }

    /**
     * Check a patient into today's queue.
     */
    public function checkIn(
        int $patientId,
        int $checkedInBy,
        ?int $doctorId = null,
        ?string $reason = null
    ): PatientQueue {
        return $this->queueRepository->create([
            'patient_id' => $patientId,
            'checked_in_by' => $checkedInBy,
            'assigned_doctor_id' => $doctorId,
            'status' => QueueStatus::Waiting,
            'queue_number' => $this->getNextQueueNumber(),
            'reason' => $reason,
            'checked_in_at' => now(),
        ]);
    }

    /**
     * Record vitals against a waiting queue entry (the nurse's pre-consultation step).
     */
    public function recordVitals(PatientQueue $entry, array $vitals, int $userId): PatientQueue
    {
        $entry->update([
            ...$vitals,
            'vitals_recorded_by' => $userId,
            'vitals_recorded_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function assignDoctor(PatientQueue $entry, int $doctorId): PatientQueue
    {
        $entry->update(['assigned_doctor_id' => $doctorId]);

        return $entry->fresh();
    }

    public function cancel(PatientQueue $entry): PatientQueue
    {
        $entry->update(['status' => QueueStatus::Cancelled]);

        return $entry->fresh();
    }

    /**
     * Mark a patient's active queue entry as in-consultation (called when a
     * consultation starts).
     */
    public function markInConsultation(int $patientId): void
    {
        $entry = $this->queueRepository->findTodayByPatient($patientId);
        $entry?->update([
            'status' => QueueStatus::InConsultation,
            'seen_at' => now(),
        ]);
    }

    /**
     * Mark a patient's queue entry completed (called when a consultation completes).
     */
    public function markCompleted(int $patientId): void
    {
        $entry = $this->queueRepository->findTodayByPatient($patientId);
        $entry?->update([
            'status' => QueueStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    private function getNextQueueNumber(): int
    {
        return $this->queueRepository->getLastTodayQueueNumber() + 1;
    }
}
