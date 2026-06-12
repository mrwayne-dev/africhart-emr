<?php

namespace App\Repositories;

use App\Models\PatientQueue;
use Illuminate\Database\Eloquent\Collection;

class PatientQueueRepository extends BaseRepository
{
    public function __construct(PatientQueue $model)
    {
        parent::__construct($model);
    }

    /**
     * Today's queue, optionally scoped to an assigned doctor, ordered by number.
     */
    public function getTodayQueue(?int $doctorId = null): Collection
    {
        return $this->model
            ->with(['patient', 'checkedInBy', 'assignedDoctor'])
            ->today()
            ->when($doctorId, fn ($q) => $q->where('assigned_doctor_id', $doctorId))
            ->orderBy('queue_number')
            ->get();
    }

    /**
     * Highest queue number issued today (0 if none).
     */
    public function getLastTodayQueueNumber(): int
    {
        return (int) $this->model->today()->max('queue_number');
    }

    /**
     * The active queue entry for a patient today (waiting or in consultation).
     */
    public function findTodayByPatient(int $patientId): ?PatientQueue
    {
        return $this->model
            ->today()
            ->active()
            ->where('patient_id', $patientId)
            ->latest()
            ->first();
    }

    public function countTodayByStatus(string $status): int
    {
        return $this->model->today()->where('status', $status)->count();
    }

    public function countTodayCheckedIn(): int
    {
        return $this->model->today()->count();
    }
}
