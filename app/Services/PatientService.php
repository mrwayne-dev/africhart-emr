<?php

namespace App\Services;

use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PatientService extends BaseService
{
    public function __construct(
        protected PatientRepository $patientRepository
    ) {
        parent::__construct($patientRepository);
    }

    /**
     * Get paginated patient list with search/filter.
     */
    public function getPatientList(
        ?string $search = null,
        ?string $bloodGroup = null,
        bool $archived = false
    ): LengthAwarePaginator {
        return $this->patientRepository->getPaginated($search, $bloodGroup, archived: $archived);
    }

    /**
     * Archive (soft-delete) a patient. The record and all its history stay in the
     * database — medical records are never destroyed.
     */
    public function archivePatient(Patient $patient): void
    {
        $patient->delete();
    }

    /**
     * Restore a previously archived patient.
     */
    public function restorePatient(Patient $patient): void
    {
        $patient->restore();
    }

    /**
     * Create a new patient with an auto-generated ID.
     */
    public function createPatient(array $data, int $registeredBy): Patient
    {
        $data['patient_id'] = $this->generatePatientId();
        $data['registered_by'] = $registeredBy;

        return $this->patientRepository->create($data);
    }

    /**
     * Update an existing patient.
     */
    public function updatePatient(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient->fresh();
    }

    /**
     * Build a chronological timeline of everything that's happened to a patient:
     * registration, consultations, and invoices. Newest first.
     */
    public function getTimeline(Patient $patient): Collection
    {
        $events = collect();

        $events->push([
            'type' => 'registration',
            'date' => $patient->created_at,
            'title' => 'Patient Registered',
            'subtitle' => 'Registered by '.($patient->registeredBy?->name ?? 'System'),
            'icon' => 'phosphor-user-plus',
            'link' => null,
        ]);

        foreach ($patient->consultations()->with('doctor')->latest()->get() as $consultation) {
            $events->push([
                'type' => 'consultation',
                'date' => $consultation->created_at,
                'title' => 'Consultation — '.($consultation->diagnosis ?: 'In Progress'),
                'subtitle' => ($consultation->doctor?->name ?? 'Unknown').' · '.$consultation->status->label(),
                'icon' => 'phosphor-stethoscope',
                'link' => route('consultations.show', $consultation),
            ]);
        }

        foreach ($patient->invoices()->latest()->get() as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->created_at,
                'title' => 'Invoice '.$invoice->invoice_number,
                'subtitle' => '₦'.number_format((float) $invoice->total, 2).' · '.$invoice->status->label(),
                'icon' => 'phosphor-receipt',
                'link' => route('invoices.show', $invoice),
            ]);
        }

        return $events->sortByDesc('date')->values();
    }

    /**
     * Generate a unique patient ID: ACH-YYYYMMDD-XXXX
     */
    private function generatePatientId(): string
    {
        $today = now()->format('Ymd');
        $prefix = "ACH-{$today}-";

        $todayCount = $this->patientRepository->countByPatientIdPrefix($prefix);
        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return $prefix.$sequence;
    }
}
