<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A subscription tier. CENTRAL — reference data shared by every clinic.
 *
 * CentralConnection pins it to the central database, so a query made from
 * inside tenant context still reads the one canonical set of plans rather than
 * looking for a `plans` table in the clinic's own database and failing.
 *
 * Money is stored in kobo as integers. Floats and currency do not belong in the
 * same sentence.
 */
#[Fillable(['slug', 'name', 'monthly_price', 'setup_fee', 'max_doctors', 'max_sites', 'features', 'is_active', 'sort_order'])]
class Plan extends Model
{
    use CentralConnection;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class, 'plan', 'slug');
    }

    /** Whether this tier includes a named feature — the B2 gating question. */
    public function includes(string $feature): bool
    {
        return (bool) ($this->features[$feature] ?? false);
    }

    public function monthlyPriceNaira(): float
    {
        return $this->monthly_price / 100;
    }

    public function setupFeeNaira(): float
    {
        return $this->setup_fee / 100;
    }
}
