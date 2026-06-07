<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $recentPatients = $this->dashboardService->getRecentPatients();

        if ($user->isAdmin()) {
            $stats = $this->dashboardService->getAdminStats();

            return view('dashboard.admin', compact('stats', 'recentPatients'));
        }

        return view('dashboard.doctor', compact('recentPatients'));
    }
}
