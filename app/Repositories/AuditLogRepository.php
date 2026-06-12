<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuditLogRepository extends BaseRepository
{
    public function __construct(AuditLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginated audit log with optional search, model-type and user filters.
     */
    public function getPaginated(
        ?string $search = null,
        ?string $modelType = null,
        ?int $userId = null,
        int $perPage = 25
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if ($modelType) {
            $query->where('model_type', $modelType);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * The most recent audit entries (for the admin activity feed).
     */
    public function getRecent(int $limit = 20): Collection
    {
        return $this->model
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Distinct model types present in the log (for the filter dropdown).
     */
    public function distinctModelTypes(): array
    {
        return $this->model->distinct()->pluck('model_type')->all();
    }
}
