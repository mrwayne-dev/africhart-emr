<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Stats for the authenticated user's role.
     */
    /**
     * @OA\Get(
     *     path="/dashboard/stats",
     *     summary="Statistics for the authenticated user's role",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=200, description="Role-specific stats")
     * )
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = match (true) {
            $user->isAdmin() => $this->dashboardService->getAdminStats(),
            $user->isNurse() => $this->dashboardService->getNurseStats(),
            $user->isReceptionist() => $this->dashboardService->getReceptionistStats(),
            default => $this->dashboardService->getDoctorStats($user->id),
        };

        return $this->success([
            'role' => $user->role->value,
            'stats' => $stats,
        ]);
    }
}
