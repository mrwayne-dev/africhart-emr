<?php

namespace App\Models;

use App\Enums\MedicationRoute;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consultation_id',
    'patient_id',
    'prescribed_by',
    'medication_id',
    'medication_name',
    'dosage',
    'frequency',
    'duration',
    'route',
    'instructions',
    'quantity',
])]
class Prescription extends Model
{
    use HasAuditTrail;

    protected function casts(): array
    {
        return [
            'route' => MedicationRoute::class,
        ];
    }

    // --- Relationships ---

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * The catalogue entry this was prescribed from, when there is one.
     *
     * Null is a legitimate state, not missing data: it means the drug is not in
     * this clinic's catalogue and was prescribed as free text. Read the name
     * through displayName() rather than assuming this relation exists.
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    /** Catalogue name when linked, otherwise what the doctor typed. */
    public function displayName(): string
    {
        return $this->medication?->name ?? (string) $this->medication_name;
    }

    /** Was this chosen from the catalogue, or typed in? */
    public function isFromCatalogue(): bool
    {
        return $this->medication_id !== null;
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prescribed_by');
    }

    // --- Accessors ---

    public function getSummaryAttribute(): string
    {
        return trim("{$this->medication_name} {$this->dosage}");
    }

    // --- Audit ---

    public function auditDescription(string $action): string
    {
        return match ($action) {
            'created' => "Prescribed {$this->medication_name} {$this->dosage}",
            'deleted' => "Removed prescription {$this->medication_name}",
            default => "{$action} prescription {$this->medication_name}",
        };
    }
}
