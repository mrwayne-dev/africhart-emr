<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'patient_id',
    'doctor_id',
    'chief_complaint',
    'clinical_notes',
    'diagnosis',
    'plan',
    'status',
    'consultation_id',
    'temperature',
    'blood_pressure',
    'pulse_rate',
    'weight',
    'height',
    'vitals_notes',
])]
class Consultation extends Model
{
    use HasAuditTrail;

    protected function casts(): array
    {
        return [
            'status' => ConsultationStatus::class,
            'temperature' => 'decimal:1',
            'weight' => 'decimal:1',
            'height' => 'decimal:1',
        ];
    }

    // --- Relationships ---

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    // --- Accessors ---

    public function getBmiAttribute(): ?float
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;

            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }

        return null;
    }

    public function getHasVitalsAttribute(): bool
    {
        return (bool) ($this->temperature || $this->blood_pressure || $this->pulse_rate);
    }

    // --- Audit ---

    public function auditDescription(string $action): string
    {
        $name = $this->patient?->full_name ?? "patient #{$this->patient_id}";

        return match ($action) {
            'created' => "Started consultation {$this->consultation_id} for {$name}",
            'updated' => "Updated consultation {$this->consultation_id} for {$name}",
            'deleted' => "Deleted consultation {$this->consultation_id}",
            default => "{$action} consultation {$this->consultation_id}",
        };
    }
}
