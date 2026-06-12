<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\AssignDoctorRequest;
use App\Http\Requests\CheckInRequest;
use App\Http\Resources\QueueEntryResource;
use App\Models\PatientQueue;
use App\Services\PatientQueueService;
use Illuminate\Http\JsonResponse;

class QueueController extends BaseController
{
    public function __construct(
        protected PatientQueueService $queueService
    ) {}

    /**
     * @OA\Get(
     *     path="/queue",
     *     summary="Today's patient queue",
     *     tags={"Queue"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(response=200, description="Queue entries")
     * )
     */
    public function index(): JsonResponse
    {
        $queue = $this->queueService->getTodayQueue();

        return $this->success(QueueEntryResource::collection($queue)->resolve());
    }

    /**
     * @OA\Post(
     *     path="/queue",
     *     summary="Check a patient into the queue",
     *     tags={"Queue"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"patient_id"},
     *
     *         @OA\Property(property="patient_id", type="integer", example=1),
     *         @OA\Property(property="assigned_doctor_id", type="integer", nullable=true),
     *         @OA\Property(property="reason", type="string", nullable=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Checked in"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(CheckInRequest $request): JsonResponse
    {
        $entry = $this->queueService->checkIn(
            patientId: $request->integer('patient_id'),
            checkedInBy: $request->user()->id,
            doctorId: $request->filled('assigned_doctor_id') ? $request->integer('assigned_doctor_id') : null,
            reason: $request->input('reason'),
        );

        return $this->success(
            (new QueueEntryResource($entry->load(['patient', 'assignedDoctor'])))->resolve(),
            'Patient checked in.',
            201,
        );
    }

    /**
     * @OA\Patch(
     *     path="/queue/{queue}/assign",
     *     summary="Assign a doctor to a queued patient",
     *     tags={"Queue"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="queue", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"assigned_doctor_id"},
     *
     *         @OA\Property(property="assigned_doctor_id", type="integer")
     *     )),
     *
     *     @OA\Response(response=200, description="Doctor assigned"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function assign(AssignDoctorRequest $request, PatientQueue $queue): JsonResponse
    {
        $entry = $this->queueService->assignDoctor($queue, $request->integer('assigned_doctor_id'));

        return $this->success(
            (new QueueEntryResource($entry->load(['patient', 'assignedDoctor'])))->resolve(),
            'Doctor assigned.',
        );
    }

    /**
     * @OA\Patch(
     *     path="/queue/{queue}/cancel",
     *     summary="Cancel a queue entry",
     *     tags={"Queue"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="queue", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Cancelled"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function cancel(PatientQueue $queue): JsonResponse
    {
        $entry = $this->queueService->cancel($queue);

        return $this->success((new QueueEntryResource($entry))->resolve(), 'Queue entry cancelled.');
    }
}
