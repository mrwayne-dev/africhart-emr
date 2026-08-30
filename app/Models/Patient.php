<?php

namespace App\Models;

use App\Enums\BloodGroup;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'full_name',
    'date_of_birth',
    'gender',
    'phone',
    'blood_group',
    'allergies',
    'emergency_contact_name',
    'emergency_contact_phone',
    'emergency_contact_relationship',
    'patient_id',
    'registered_by',
])]
class Patient extends Model
{
    use HasAuditTrail, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'blood_group' => BloodGroup::class,
        ];
    }

    // --- Relationships ---

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'registered_by');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(PatientQueue::class);
    }

    // --- Accessors ---

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    // --- Audit ---

    public function auditDescription(string $action): string
    {
        return match ($action) {
            'created' => "Registered patient {$this->full_name}",
            'updated' => "Updated patient {$this->full_name}",
            'deleted' => "Deleted patient {$this->full_name}",
            default => "{$action} patient {$this->full_name}",
        };
    }
}
