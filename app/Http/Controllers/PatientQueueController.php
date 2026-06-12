<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\RendersLiveFragment;
use App\Http\Requests\AssignDoctorRequest;
use App\Http\Requests\CheckInRequest;
use App\Models\Patient;
use App\Models\PatientQueue;
use App\Models\User;
use App\Services\PatientQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientQueueController extends BaseController
{
    use RendersLiveFragment;

    public function __construct(
        protected PatientQueueService $queueService
    ) {}

    public function index(): View
    {
        $queue = $this->queueService->getTodayQueue();

        return view('queue.index', [
            'queue' => $queue,
            'liveHash' => $this->liveHash($this->queueHashParts($queue)),
            'patients' => Patient::orderBy('full_name')->get(['id', 'full_name', 'patient_id']),
            'doctors' => $this->doctors(),
        ]);
    }

    /**
     * Live fragment for polling: today's queue table + a change-hash.
     */
    public function live(Request $request): JsonResponse
    {
        $doctorId = $request->boolean('mine') ? $request->user()->id : null;
        $queue = $this->queueService->getTodayQueue($doctorId);

        return $this->liveFragment(
            'queue.partials.list',
            ['queue' => $queue, 'doctors' => $this->doctors()],
            $this->queueHashParts($queue),
            ['count' => $queue->count()],
        );
    }

    /**
     * @return array<int, string>
     */
    private function queueHashParts($queue): array
    {
        return $this->queueService->liveHashParts($queue);
    }

    private function doctors()
    {
        return User::where('role', UserRole::Doctor->value)->orderBy('name')->get(['id', 'name']);
    }

    public function store(CheckInRequest $request): RedirectResponse
    {
        $entry = $this->queueService->checkIn(
            patientId: $request->integer('patient_id'),
            checkedInBy: auth()->id(),
            doctorId: $request->filled('assigned_doctor_id') ? $request->integer('assigned_doctor_id') : null,
            reason: $request->input('reason'),
        );

        return back()->with('success', "Patient checked in as #{$entry->queue_number}.");
    }

    public function assign(AssignDoctorRequest $request, PatientQueue $queue): RedirectResponse
    {
        $this->queueService->assignDoctor($queue, $request->integer('assigned_doctor_id'));

        return back()->with('success', 'Doctor assigned to the patient.');
    }

    public function cancel(PatientQueue $queue): RedirectResponse
    {
        $this->queueService->cancel($queue);

        return back()->with('success', 'Queue entry cancelled.');
    }
}
