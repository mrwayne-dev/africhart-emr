<?php

namespace App\Repositories;

use App\Models\Prescription;
use Illuminate\Database\Eloquent\Collection;

class PrescriptionRepository extends BaseRepository
{
    public function __construct(Prescription $model)
    {
        parent::__construct($model);
    }

    /**
     * All prescriptions for a given consultation.
     */
    public function forConsultation(int $consultationId): Collection
    {
        return $this->model
            ->where('consultation_id', $consultationId)
            ->latest()
            ->get();
    }
}
