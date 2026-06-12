<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\JsonResponse;

class PrescriptionController extends BaseController
{
    public function __construct(
        protected PrescriptionService $prescriptionService
    ) {}

    /**
     * @OA\Get(
     *     path="/consultations/{consultation}/prescriptions",
     *     summary="List prescriptions for a consultation",
     *     tags={"Prescriptions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Prescription list")
     * )
     */
    public function index(Consultation $consultation): JsonResponse
    {
        $this->authorize('view', $consultation);

        $prescriptions = $consultation->prescriptions()->with('prescribedBy')->latest()->get();

        return $this->success(PrescriptionResource::collection($prescriptions)->resolve());
    }

    /**
     * @OA\Post(
     *     path="/consultations/{consultation}/prescriptions",
     *     summary="Add one or more prescriptions to a consultation",
     *     tags={"Prescriptions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="consultation", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"items"},
     *
     *         @OA\Property(property="items", type="array", @OA\Items(
     *             required={"medication_name","dosage","frequency","duration","route"},
     *             @OA\Property(property="medication_name", type="string", example="Paracetamol"),
     *             @OA\Property(property="dosage", type="string", example="500mg"),
     *             @OA\Property(property="frequency", type="string", example="3 times daily"),
     *             @OA\Property(property="duration", type="string", example="5 days"),
     *             @OA\Property(property="route", type="string", example="oral"),
     *             @OA\Property(property="quantity", type="integer", example=20)
     *         ))
     *     )),
     *
     *     @OA\Response(response=201, description="Prescriptions added"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(StorePrescriptionRequest $request, Consultation $consultation): JsonResponse
    {
        $this->authorize('update', $consultation);

        $created = collect($request->validated()['items'])
            ->map(fn (array $item) => $this->prescriptionService->addToConsultation($consultation, $item, $request->user()->id));

        return $this->success(
            PrescriptionResource::collection($created)->resolve(),
            count($created).' prescription(s) added.',
            201,
        );
    }

    /**
     * @OA\Delete(
     *     path="/prescriptions/{prescription}",
     *     summary="Remove a prescription (owner doctor or admin)",
     *     tags={"Prescriptions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="prescription", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Removed"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Prescription $prescription): JsonResponse
    {
        $this->authorize('update', $prescription->consultation);

        $this->prescriptionService->remove($prescription);

        return $this->success([], 'Prescription removed.');
    }
}
