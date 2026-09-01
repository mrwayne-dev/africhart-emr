<?php

namespace App\Repositories;

use App\Models\Patient;
use App\Support\ClinicIdentity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientRepository extends BaseRepository
{
    public function __construct(Patient $model)
    {
        parent::__construct($model);
    }

    /**
     * Get paginated patients with optional search and blood-group filter.
     */
    public function getPaginated(
        ?string $search = null,
        ?string $bloodGroup = null,
        int $perPage = 15,
        bool $archived = false
    ): LengthAwarePaginator {
        $query = $this->model->with('registeredBy');

        if ($archived) {
            $query->onlyTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('patient_id', 'like', "%{$search}%");
            });
        }

        if ($bloodGroup) {
            $query->where('blood_group', $bloodGroup);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * Get recent patients with limit.
     */
    public function getRecent(int $limit = 10)
    {
        return $this->model
            ->with('registeredBy')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Count all patients.
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Count patients created today.
     */
    public function countToday(): int
    {
        /*
         * The CLINIC's day, as a UTC range — not whereDate against the UTC
         * calendar day.
         *
         * Timestamps are stored in UTC. A clinic in Africa/Lagos starts its day
         * an hour before UTC does, so its day is not any single UTC date and
         * whereDate cannot express it. A patient checked in at 00:30 Lagos is
         * stored 23:30 UTC the previous day: under whereDate they vanished from
         * "today" an hour later, and the daily queue_number sequence — which
         * counts today's rows — could hand the same number out twice.
         */
        return $this->model->whereBetween('created_at', ClinicIdentity::todayRange())->count();
    }

    /**
     * Count patients created this week.
     */
    public function countThisWeek(): int
    {
        return $this->model->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();
    }

    /**
     * Count patients whose patient_id starts with the given prefix (for ID generation).
     */
    public function countByPatientIdPrefix(string $prefix): int
    {
        return $this->model->where('patient_id', 'like', $prefix.'%')->count();
    }
}
