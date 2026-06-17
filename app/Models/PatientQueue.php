<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id',
    'checked_in_by',
    'assigned_doctor_id',
    'status',
    'queue_number',
    'reason',
    'checked_in_at',
    'seen_at',
    'completed_at',
    'temperature',
    'blood_pressure',
    'pulse_rate',
    'weight',
    'height',
    'vitals_notes',
    'vitals_recorded_by',
    'vitals_recorded_at',
])]
class PatientQueue extends Model
{
    protected $table = 'patient_queue';

    protected function casts(): array
    {
        return [
            'status' => QueueStatus::class,
            'checked_in_at' => 'datetime',
            'seen_at' => 'datetime',
            'completed_at' => 'datetime',
            'temperature' => 'decimal:1',
            'weight' => 'decimal:1',
            'height' => 'decimal:1',
            'vitals_recorded_at' => 'datetime',
        ];
    }

    /**
     * The vitals fields a consultation absorbs on start.
     *
     * @var array<int, string>
     */
    public const VITALS_FIELDS = ['temperature', 'blood_pressure', 'pulse_rate', 'weight', 'height', 'vitals_notes'];

    // --- Relationships ---

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function vitalsRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vitals_recorded_by');
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

    // --- Scopes ---

    public function scopeToday($query)
    {
        return $query->whereDate('checked_in_at', today());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [QueueStatus::Waiting, QueueStatus::InConsultation]);
    }
}
