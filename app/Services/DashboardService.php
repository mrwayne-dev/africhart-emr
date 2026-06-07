<?php

namespace App\Services;

use App\Repositories\PatientRepository;

class DashboardService extends BaseService
{
    public function __construct(
        protected PatientRepository $patientRepository
    ) {
        parent::__construct($patientRepository);
    }

    /**
     * Get admin dashboard statistics.
     */
    public function getAdminStats(): array
    {
        return [
            'total_patients' => $this->patientRepository->count(),
            'today_registered' => $this->patientRepository->countToday(),
            'this_week' => $this->patientRepository->countThisWeek(),
        ];
    }

    /**
     * Get recent patients for dashboard display.
     */
    public function getRecentPatients(int $limit = 10)
    {
        return $this->patientRepository->getRecent($limit);
    }
}
