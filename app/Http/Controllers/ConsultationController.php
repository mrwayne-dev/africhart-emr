<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLiveFragment;
use App\Http\Requests\RecordVitalsRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\Patient;
use App\Services\AdminNotifier;
use App\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultationController extends BaseController
{
    use RendersLiveFragment;

    public function __construct(
        protected ConsultationService $consultationService,
        protected AdminNotifier $adminNotifier,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Consultation::class);

        $consultations = $this->consultationService->getConsultationList(
            search: $request->input('search'),
            status: $request->input('status'),
            doctorId: $request->boolean('mine') ? $request->user()->id : null,
        );

        return view('consultations.index', [
            'consultations' => $consultations,
            'liveHash' => $this->liveHash($this->listHashParts($consultations)),
        ]);
    }

    /**
     * Live fragment for the consultation list (respects the current filters).
     */
    public function liveIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Consultation::class);

        $consultations = $this->consultationService->getConsultationList(
            search: $request->input('search'),
            status: $request->input('status'),
            doctorId: $request->boolean('mine') ? $request->user()->id : null,
        );

        return $this->liveFragment('consultations.partials.list', compact('consultations'), $this->listHashParts($consultations));
    }

    /**
     * @return array<int, string>
     */
    private function listHashParts($consultations): array
    {
        $parts = $consultations->getCollection()
            ->map(fn ($c) => $c->id.'|'.$c->status->value.'|'.$c->updated_at)
            ->all();
        $parts[] = 'page:'.$consultations->currentPage();

        return $parts;
    }

    /**
     * Hash-only live endpoint for the consultation detail (notify mode — the page
     * shows a "refresh" banner rather than swapping, to protect unsaved input).
     */
    public function liveShow(Consultation $consultation): JsonResponse
    {
        $this->authorize('view', $consultation);

        return $this->liveHashResponse($this->detailHashParts($consultation));
    }

    /**
     * @return array<int, mixed>
     */
    private function detailHashParts(Consultation $consultation): array
    {
        return [
            $consultation->updated_at?->timestamp,
            $consultation->status->value,
            (bool) $consultation->has_vitals,
            (string) $consultation->prescriptions()->max('updated_at'),
            $consultation->prescriptions()->count(),
            $consultation->invoice?->id,
        ];
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Consultation::class);

        return view('consultations.create', [
            'patients' => Patient::orderBy('full_name')->get(['id', 'full_name', 'patient_id']),
            'selectedPatientId' => $request->integer('patient_id') ?: null,
        ]);
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        $this->authorize('create', Consultation::class);

        $consultation = $this->consultationService->startConsultation(
            data: $request->validated(),
            doctorId: $request->user()->id,
        );

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', 'Consultation started — '.$consultation->consultation_id);
    }

    public function show(Consultation $consultation): View
    {
        $this->authorize('view', $consultation);

        $consultation->load(['patient', 'doctor', 'prescriptions.prescribedBy', 'invoice']);

        return view('consultations.show', [
            'consultation' => $consultation,
            'medications' => $this->loadMedicationPresets(),
            'liveHash' => $this->liveHash($this->detailHashParts($consultation)),
        ]);
    }

    /**
     * Common-medication presets for the prescription autocomplete.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadMedicationPresets(): array
    {
        $path = storage_path('app/data/medications.json');

        return is_file($path)
            ? (json_decode(file_get_contents($path), true) ?: [])
            : [];
    }

    public function edit(Consultation $consultation): View
    {
        $this->authorize('update', $consultation);

        $consultation->load('patient');

        return view('consultations.edit', compact('consultation'));
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorize('update', $consultation);

        $this->consultationService->updateConsultation($consultation, $request->validated());

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', 'Consultation updated.');
    }

    public function recordVitals(RecordVitalsRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorize('recordVitals', $consultation);

        $this->consultationService->recordVitals($consultation, $request->validated());

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', 'Vitals recorded.');
    }

    public function complete(Consultation $consultation): RedirectResponse
    {
        $this->authorize('complete', $consultation);

        $this->consultationService->completeConsultation($consultation);

        $this->adminNotifier->consultationCompleted($consultation, request()->user());

        return redirect()
            ->route('consultations.show', $consultation)
            ->with('success', 'Consultation completed.');
    }
}
