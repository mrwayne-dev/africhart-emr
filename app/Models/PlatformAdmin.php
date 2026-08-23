<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A platform operator — us, not clinic staff (D5).
 *
 * Authenticates on admin.africhartemr.com through its own `admin` guard against
 * its own central table. It is deliberately NOT the same model as clinic staff:
 * one guard resolving to two tables is how an operator ends up authenticated as
 * a clinician, or a clinician as an operator, and that mistake reaches every
 * clinic at once rather than one.
 *
 * CentralConnection pins it to the central database, so it stays reachable and
 * unchanged even from inside a tenant request — which is what B5's audited
 * impersonation will need.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class PlatformAdmin extends Authenticatable
{
    use CentralConnection, Notifiable, SoftDeletes;

    protected $table = 'platform_admins';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
