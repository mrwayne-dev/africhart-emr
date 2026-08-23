<?php

namespace App\Models;

use App\Enums\StaffRole;
use App\Services\EmailVerificationService;
use Database\Factories\StaffFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A member of ONE clinic's staff. Lives in that clinic's own database.
 *
 * Renamed from User (ARCHITECTURE.md D5, §5). The clinic's people are staff of
 * that clinic; platform operators are App\Models\PlatformAdmin in the central
 * database, behind their own guard. One guard resolving to two tables is how an
 * operator ends up authenticated as a clinician — a mistake whose blast radius
 * is every clinic at once.
 *
 * The `web` guard resolves here. There is no tenant-side concept of a platform
 * operator and no central concept of clinical staff.
 */
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token', 'email_verification_code'])]
class Staff extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<StaffFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /*
     * Explicit: Laravel would infer `staffs` from the class name. It is also a
     * reminder that this table is per-tenant — there is no `staff` table in the
     * central database at all.
     */
    protected $table = 'staff';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'role' => StaffRole::class,
        ];
    }

    /**
     * Send our 6-digit code instead of Laravel's default verification link.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationService::class)->sendCode($this);
    }

    // --- Role helpers ---

    public function isAdmin(): bool
    {
        return $this->role === StaffRole::Admin;
    }

    public function isDoctor(): bool
    {
        return $this->role === StaffRole::Doctor;
    }

    public function isNurse(): bool
    {
        return $this->role === StaffRole::Nurse;
    }

    public function isReceptionist(): bool
    {
        return $this->role === StaffRole::Receptionist;
    }

    /**
     * Doctors and nurses are the staff who handle clinical care.
     */
    public function isClinicalStaff(): bool
    {
        return in_array($this->role, [StaffRole::Doctor, StaffRole::Nurse], true);
    }

    // --- Relationships ---

    public function registeredPatients(): HasMany
    {
        return $this->hasMany(Patient::class, 'registered_by');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'doctor_id');
    }
}
