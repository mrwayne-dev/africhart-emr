<?php

namespace App\Models;

use App\Enums\BloodGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'full_name',
    'date_of_birth',
    'phone',
    'blood_group',
    'allergies',
    'patient_id',
    'registered_by',
])]
class Patient extends Model
{
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
        return $this->belongsTo(User::class, 'registered_by');
    }

    // --- Accessors ---

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }
}
