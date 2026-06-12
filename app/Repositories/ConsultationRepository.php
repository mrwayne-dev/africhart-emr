<?php

namespace App\Repositories;

use App\Enums\ConsultationStatus;
use App\Models\Consultation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ConsultationRepository extends BaseRepository
{
    public function __construct(Consultation $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginated consultation list with optional search, status and doctor filters.
     */
    public function getPaginated(
        ?string $search = null,
        ?string $status = null,
        ?int $doctorId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->with(['patient', 'doctor']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('consultation_id', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($p) use ($search) {
                        $p->where('full_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * Recent consultations, optionally scoped to a doctor.
     */
    public function getRecent(int $limit = 10, ?int $doctorId = null)
    {
        return $this->model
            ->with(['patient', 'doctor'])
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->latest()
            ->take($limit)
            ->get();
    }

    public function countToday(?int $doctorId = null): int
    {
        return $this->model
            ->whereDate('created_at', today())
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->count();
    }

    public function countThisWeek(?int $doctorId = null): int
    {
        return $this->model
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->count();
    }

    public function countForDoctor(int $doctorId): int
    {
        return $this->model->where('doctor_id', $doctorId)->count();
    }

    /**
     * Completed consultations that don't yet have an invoice — the receptionist's
     * billing worklist.
     */
    public function completedWithoutInvoice(int $limit = 20): Collection
    {
        return $this->model
            ->with(['patient', 'doctor'])
            ->where('status', ConsultationStatus::Completed->value)
            ->whereDoesntHave('invoice')
            ->latest('updated_at')
            ->take($limit)
            ->get();
    }

    public function countByConsultationIdPrefix(string $prefix): int
    {
        return $this->model->where('consultation_id', 'like', $prefix.'%')->count();
    }

    public function statusBreakdown(): array
    {
        return $this->model
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
