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

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
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
