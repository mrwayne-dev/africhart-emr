<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Support\ClinicIdentity;
use App\Traits\HasAuditTrail;
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
    use HasAuditTrail;

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
        return $this->belongsTo(Staff::class, 'checked_in_by');
    }

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_doctor_id');
    }

    public function vitalsRecordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'vitals_recorded_by');
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
        /*
         * The CLINIC's day, as a UTC range — not whereDate against the UTC
         * calendar day.
         *
         * Timestamps are stored in UTC. A clinic in Africa/Lagos starts its day
         * an hour before UTC does, so its day is not any single UTC date and
         * whereDate cannot express it. A patient checked in at 00:30 Lagos is
         * stored 23:30 UTC the previous day: under whereDate they vanished from
         * "today" an hour later, and the daily queue_number sequence — which
         * counts today's rows — could hand the same number out twice.
         */
        return $query->whereBetween('checked_in_at', ClinicIdentity::todayRange());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [QueueStatus::Waiting, QueueStatus::InConsultation]);
    }

    /**
     * What actually happened, in the words a clinic would use.
     *
     * The generic trait records created/updated/deleted. For the queue that is
     * not enough: a check-in and a set of vitals are both "updated" rows to the
     * database, but they are different clinical events performed by different
     * people, and an audit trail that cannot tell them apart cannot answer the
     * question it exists to answer.
     *
     * The queue was not audited AT ALL before this — which meant the nurse, whose
     * entire contribution to a visit is recorded here, left no trace in the log.
     * A whole clinical role was invisible.
     *
     * wasChanged() is available inside the `updated` event because Eloquent
     * populates $changes during finishSave(), before the event fires.
     */
    public function auditDescription(string $action): string
    {
        $patient = $this->patient?->full_name ?? "patient #{$this->patient_id}";

        if ($action === 'created') {
            $doctor = $this->assignedDoctor?->name;

            return $doctor
                ? "Checked in {$patient} as #{$this->queue_number}, assigned to {$doctor}"
                : "Checked in {$patient} as #{$this->queue_number}, no doctor assigned yet";
        }

        if ($action === 'deleted') {
            return "Removed {$patient} from the queue";
        }

        // `updated` covers several distinct clinical events. Name the one that
        // actually happened, most specific first.
        if ($this->wasChanged('vitals_recorded_at') || $this->wasChanged(['temperature', 'blood_pressure', 'pulse_rate', 'weight', 'height', 'vitals_notes'])) {
            $vitals = array_filter([
                $this->temperature ? "temp {$this->temperature}C" : null,
                $this->blood_pressure ? "BP {$this->blood_pressure}" : null,
                $this->pulse_rate ? "pulse {$this->pulse_rate}" : null,
            ]);

            return "Recorded vitals for {$patient}".($vitals ? ' — '.implode(', ', $vitals) : '');
        }

        if ($this->wasChanged('assigned_doctor_id')) {
            $doctor = $this->assignedDoctor?->name ?? 'nobody';

            return "Assigned {$patient} to {$doctor}";
        }

        if ($this->wasChanged('status')) {
            $status = $this->status instanceof QueueStatus ? $this->status->value : (string) $this->status;

            return "Queue status for {$patient} changed to {$status}";
        }

        return "Updated queue entry for {$patient}";
    }
}
