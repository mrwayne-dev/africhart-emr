<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\AdminNotifier;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PatientController extends BaseController
{
    public function __construct(
        protected PatientService $patientService,
        protected AdminNotifier $adminNotifier,
    ) {}

    public function index(Request $request): View
    {
        $archived = $request->boolean('archived');

        $patients = $this->patientService->getPatientList(
            search: $request->input('search'),
            bloodGroup: $request->input('blood_group'),
            archived: $archived,
        );

        return view('patients.index', compact('patients', 'archived'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(StorePatientRequest $request): RedirectResponse|JsonResponse
    {
        // Idempotency: a retried submit (network blip) carries the same key, so we
        // return the already-created patient instead of inserting a duplicate.
        $idempotencyKey = $request->header('X-Idempotency-Key');
        $cacheKey = $idempotencyKey ? "patient-create:{$idempotencyKey}" : null;

        if ($cacheKey && ($existingId = Cache::get($cacheKey))) {
            return $this->patientStoredResponse($request, Patient::findOrFail($existingId), duplicate: true);
        }

        $patient = $this->patientService->createPatient(
            data: $request->validated(),
            registeredBy: auth()->id(),
        );

        if ($cacheKey) {
            Cache::put($cacheKey, $patient->id, now()->addMinutes(10));
        }

        $this->adminNotifier->patientRegistered($patient, $request->user());

        return $this->patientStoredResponse($request, $patient);
    }

    private function patientStoredResponse(Request $request, Patient $patient, bool $duplicate = false): RedirectResponse|JsonResponse
    {
        $message = $duplicate
            ? 'Patient already registered. ID: '.$patient->patient_id
            : 'Patient registered successfully. ID: '.$patient->patient_id;

        if ($request->wantsJson()) {
            // Flash so the toast shows after the modal redirects the page.
            session()->flash('success', $message);

            return response()->json(['redirect' => route('patients.show', $patient)]);
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', $message);
    }

    public function show(Patient $patient): View
    {
        $patient->load('registeredBy');

        return view('patients.show', [
            'patient' => $patient,
            'timeline' => $this->patientService->getTimeline($patient),
        ]);
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse|JsonResponse
    {
        $this->patientService->updatePatient($patient, $request->validated());

        $this->adminNotifier->patientUpdated($patient, $request->user());

        $message = 'Patient record updated successfully.';

        if ($request->wantsJson()) {
            session()->flash('success', $message);

            return response()->json(['redirect' => route('patients.show', $patient)]);
        }

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', $message);
    }

    public function archive(Patient $patient): RedirectResponse
    {
        $this->patientService->archivePatient($patient);

        return redirect()
            ->route('patients.index')
            ->with('success', "Patient {$patient->full_name} archived. The record is preserved and can be restored.");
    }

    public function restore(Patient $patient): RedirectResponse
    {
        $this->patientService->restorePatient($patient);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', "Patient {$patient->full_name} restored.");
    }
}
