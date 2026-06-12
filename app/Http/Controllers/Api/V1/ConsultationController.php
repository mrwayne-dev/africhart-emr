<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\RecordVitalsRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationController extends BaseController
{
    public function __construct(
        protected ConsultationService $consultationService
    ) {}

    /**
     * @OA\Get(
     *     path="/consultations",
     *     summary="List consultations (paginated)",
     *     tags={"Consultations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"in_progress","completed","follow_up"})),
     *     @OA\Parameter(name="mine", in="query", required=false, @OA\Schema(type="boolean")),
     *
     *     @OA\Response(response=200, description="Paginated consultation list"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Consultation::class);

        $consultations = $this->consultationService->getConsultationList(
            search: $request->input('search'),
            status: $request->input('status'),
            doctorId: $request->input('mine') ? $request->user()->id : null,
        );

        $consultations->through(fn (Consultation $c) => (new ConsultationResource($c->loadMissing(['patient', 'doctor'])))->resolve());

        return $this->paginated($consultations);
    }

    /**
     * @OA\Post(
     *     path="/consultations",
     *     summary="Start a consultation",
     *     tags={"Consultations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"patient_id","chief_complaint","clinical_notes"},
     *
     *         @OA\Property(property="patient_id", type="integer", example=1),
     *         @OA\Property(property="chief_complaint", type="string"),
     *         @OA\Property(property="clinical_notes", type="string"),
     *         @OA\Property(property="diagnosis", type="string", nullable=true),
     *         @OA\Property(property="plan", type="string", nullable=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Consultation started"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreConsultationRequest $request): JsonResponse
    {
        $this->authorize('create', Consultation::class);

        $consultation = $this->consultationService->startConsultation(
            data: $request->validated(),
            doctorId: $request->user()->id,
        );

        return $this->success(
            (new ConsultationResource($consultation->load(['patient', 'doctor'])))->resolve(),
            'Consultation started.',
            201,
        );
    }

    /**
     * @OA\Get(
     *     path="/consultations/{consultation}",
     *     summary="Show a consultation (with prescriptions + invoice)",
     *     tags={"Consultations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Consultation detail"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(Consultation $consultation): JsonResponse
    {
        $this->authorize('view', $consultation);

        $consultation->load(['patient', 'doctor', 'prescriptions.prescribedBy', 'invoice']);

        return $this->success((new ConsultationResource($consultation))->resolve());
    }

    /**
     * @OA\Put(
     *     path="/consultations/{consultation}",
     *     summary="Update consultation notes (owner doctor or admin)",
     *     tags={"Consultations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(UpdateConsultationRequest $request, Consultation $consultation): JsonResponse
    {
        $this->authorize('update', $consultation);

        $consultation = $this->consultationService->updateConsultation($consultation, $request->validated());

        return $this->success((new ConsultationResource($consultation))->resolve(), 'Consultation updated.');
    }

    /**
     * @OA\Patch(
     *     path="/consultations/{consultation}/vitals",
     *     summary="Record vitals (doctor, nurse or admin)",
     *     tags={"Consultations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(@OA\JsonContent(
     *
     *         @OA\Property(property="temperature", type="number", example=37.2),
     *         @OA\Property(property="blood_pressure", type="string", example="120/80"),
     *         @OA\Property(property="pulse_rate", type="integer", example=72),
     *         @OA\Property(property="weight", type="number", example=68),
     *         @OA\Property(property="height", type="number", example=170)
     *     )),
     *
     *     @OA\Response(response=200, description="Vitals recorded"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function vitals(RecordVitalsRequest $request, Consultation $consultation): JsonResponse
    {
        $this->authorize('recordVitals', $consultation);

        $consultation = $this->consultationService->recordVitals($consultation, $request->validated());

        return $this->success((new ConsultationResource($consultation))->resolve(), 'Vitals recorded.');
    }

    /**
     * @OA\Patch(
     *     path="/consultations/{consultation}/complete",
     *     summary="Mark a consultation completed",
     *     tags={"Consultations"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Completed"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function complete(Consultation $consultation): JsonResponse
    {
        $this->authorize('complete', $consultation);

        $consultation = $this->consultationService->completeConsultation($consultation);

        return $this->success((new ConsultationResource($consultation))->resolve(), 'Consultation completed.');
    }
}
