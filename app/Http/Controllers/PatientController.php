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
use Illuminate\View\View;

class PatientController extends BaseController
{
    public function __construct(
        protected PatientService $patientService,
        protected AdminNotifier $adminNotifier,
    ) {}

    public function index(Request $request): View
    {
        $patients = $this->patientService->getPatientList(
            search: $request->input('search'),
            bloodGroup: $request->input('blood_group'),
        );

        return view('patients.index', compact('patients'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(StorePatientRequest $request): RedirectResponse|JsonResponse
    {
        $patient = $this->patientService->createPatient(
            data: $request->validated(),
            registeredBy: auth()->id(),
        );

        $this->adminNotifier->patientRegistered($patient, $request->user());

        $message = 'Patient registered successfully. ID: '.$patient->patient_id;

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

        return view('patients.show', compact('patient'));
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
}
