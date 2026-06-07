<?php

namespace App\Repositories;

use App\Models\Patient;
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
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->with('registeredBy');

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
        return $this->model->whereDate('created_at', today())->count();
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
