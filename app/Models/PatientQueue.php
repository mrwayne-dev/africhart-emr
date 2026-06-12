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
        ];
    }

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
