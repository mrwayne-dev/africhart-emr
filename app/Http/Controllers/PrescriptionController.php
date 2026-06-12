<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PrescriptionController extends BaseController
{
    public function __construct(
        protected PrescriptionService $prescriptionService
    ) {}

    public function store(StorePrescriptionRequest $request, Consultation $consultation): RedirectResponse
    {
        // Adding a prescription is editing the consultation — owner doctor or admin.
        $this->authorize('update', $consultation);

        $count = 0;
        foreach ($request->validated()['items'] as $item) {
            $this->prescriptionService->addToConsultation($consultation, $item, $request->user()->id);
            $count++;
        }

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', $count.' '.Str::plural('prescription', $count).' added.');
    }

    public function destroy(Prescription $prescription): RedirectResponse
    {
        $this->authorize('update', $prescription->consultation);

        $consultation = $prescription->consultation;
        $this->prescriptionService->remove($prescription);

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', 'Prescription removed.');
    }
}
