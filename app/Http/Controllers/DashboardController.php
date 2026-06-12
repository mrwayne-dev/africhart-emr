<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\RendersLiveFragment;
use App\Models\Patient;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\PatientQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    use RendersLiveFragment;

    public function __construct(
        protected DashboardService $dashboardService,
        protected PatientQueueService $queueService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $ready = $this->dashboardService->getReadyToInvoice();

            return view('dashboard.admin', [
                'stats' => $this->dashboardService->getAdminStats(),
                'recentPatients' => $this->dashboardService->getRecentPatients(),
                'readyToInvoice' => $ready,
                'readyLiveHash' => $this->readyHash($ready),
                'registrationTrend' => $this->dashboardService->getRegistrationTrend(),
                'revenueTrend' => $this->dashboardService->getRevenueTrend(),
                'consultationBreakdown' => $this->dashboardService->getConsultationStatusBreakdown(),
                'activityFeed' => $this->dashboardService->getActivityFeed(),
            ]);
        }

        if ($user->isNurse()) {
            $queue = $this->queueService->getTodayQueue();

            return view('dashboard.nurse', [
                'stats' => $this->dashboardService->getNurseStats(),
                'queue' => $queue,
                'queueLiveHash' => $this->liveHash($this->queueService->liveHashParts($queue)),
                ...$this->checkInLists(),
            ]);
        }

        if ($user->isReceptionist()) {
            $queue = $this->queueService->getTodayQueue();
            $ready = $this->dashboardService->getReadyToInvoice();

            return view('dashboard.receptionist', [
                'stats' => $this->dashboardService->getReceptionistStats(),
                'queue' => $queue,
                'queueLiveHash' => $this->liveHash($this->queueService->liveHashParts($queue)),
                'readyToInvoice' => $ready,
                'readyLiveHash' => $this->readyHash($ready),
                ...$this->checkInLists(),
            ]);
        }

        // Doctor
        $queue = $this->queueService->getTodayQueue($user->id);

        return view('dashboard.doctor', [
            'stats' => $this->dashboardService->getDoctorStats($user->id),
            'queue' => $queue,
            'queueLiveHash' => $this->liveHash($this->queueService->liveHashParts($queue)),
            'recentConsultations' => $this->dashboardService->getRecentConsultations(10, $user->id),
        ]);
    }

    /**
     * Live fragment for the "Ready to Invoice" billing worklist (admin/receptionist).
     */
    public function readyToInvoiceLive(): JsonResponse
    {
        $consultations = $this->dashboardService->getReadyToInvoice();

        return $this->liveFragment(
            'billing.partials.ready-to-invoice',
            ['consultations' => $consultations],
            $this->readyParts($consultations),
            ['count' => $consultations->count()],
        );
    }

    /**
     * @return array<int, string>
     */
    private function readyParts(Collection $consultations): array
    {
        return $consultations->map(fn ($c) => $c->id.'|'.$c->updated_at)->all();
    }

    private function readyHash(Collection $consultations): string
    {
        return $this->liveHash($this->readyParts($consultations));
    }

    /**
     * Patient + doctor lists used to populate the check-in modal.
     *
     * @return array{patients: Collection, doctors: Collection}
     */
    private function checkInLists(): array
    {
        return [
            'patients' => Patient::orderBy('full_name')->get(['id', 'full_name', 'patient_id']),
            'doctors' => User::where('role', UserRole::Doctor->value)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
