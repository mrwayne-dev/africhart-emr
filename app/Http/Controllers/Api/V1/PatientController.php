<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends BaseController
{
    public function __construct(
        protected PatientService $patientService
    ) {}

    /**
     * @OA\Get(
     *     path="/patients",
     *     summary="List patients (paginated, searchable)",
     *     tags={"Patients"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="blood_group", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Paginated patient list")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $patients = $this->patientService->getPatientList(
            search: $request->input('search'),
            bloodGroup: $request->input('blood_group'),
        );

        $patients->through(fn (Patient $p) => (new PatientResource($p->loadMissing('registeredBy')))->resolve());

        return $this->paginated($patients);
    }

    /**
     * @OA\Post(
     *     path="/patients",
     *     summary="Register a new patient",
     *     tags={"Patients"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"full_name","date_of_birth","phone","blood_group"},
     *
     *         @OA\Property(property="full_name", type="string", example="Chioma Nwosu"),
     *         @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-01"),
     *         @OA\Property(property="phone", type="string", example="08031234567"),
     *         @OA\Property(property="blood_group", type="string", example="O+"),
     *         @OA\Property(property="allergies", type="string", nullable=true)
     *     )),
     *
     *     @OA\Response(response=201, description="Patient created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->createPatient(
            data: $request->validated(),
            registeredBy: $request->user()->id,
        );

        return $this->success(
            (new PatientResource($patient))->resolve(),
            'Patient created successfully.',
            201,
        );
    }

    /**
     * @OA\Get(
     *     path="/patients/{patient}",
     *     summary="Show one patient",
     *     tags={"Patients"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="patient", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Patient"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Patient $patient): JsonResponse
    {
        return $this->success((new PatientResource($patient->load('registeredBy')))->resolve());
    }

    /**
     * @OA\Put(
     *     path="/patients/{patient}",
     *     summary="Update a patient",
     *     tags={"Patients"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="patient", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *
     *         @OA\Property(property="full_name", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="blood_group", type="string")
     *     )),
     *
     *     @OA\Response(response=200, description="Patient updated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient = $this->patientService->updatePatient($patient, $request->validated());

        return $this->success((new PatientResource($patient))->resolve(), 'Patient updated successfully.');
    }

    /**
     * @OA\Get(
     *     path="/patients/{patient}/timeline",
     *     summary="Patient visit history (registration, consultations, invoices)",
     *     tags={"Patients"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="patient", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Chronological timeline")
     * )
     */
    public function timeline(Patient $patient): JsonResponse
    {
        $timeline = $this->patientService->getTimeline($patient)
            ->map(fn (array $event) => [
                'type' => $event['type'],
                'date' => $event['date']?->toISOString(),
                'title' => $event['title'],
                'subtitle' => $event['subtitle'],
            ]);

        return $this->success($timeline);
    }
}
