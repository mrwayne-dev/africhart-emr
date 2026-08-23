<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * A clinic — the tenant.
 *
 * Configured as `tenancy.tenant_model`, so this is what stancl resolves a
 * subdomain to and what its provisioning jobs act on. It lives in the CENTRAL
 * database (the CentralConnection concern on the parent enforces that), and
 * every clinical record it owns lives in a database of its own.
 *
 * ⚠️ getCustomColumns() is load-bearing, not boilerplate. stancl models use
 * stancl/virtualcolumn: any attribute NOT listed there is JSON-encoded into the
 * `data` column rather than written to its own column — silently, with no
 * error. Add a column to the migration and forget it here and reads still
 * "work" while queries and WHERE clauses against that column quietly match
 * nothing. The list below must mirror create_clinics_table exactly.
 */
class Clinic extends BaseTenant implements TenantWithDatabase
{
    /*
     * HasDatabase only. NOT HasDomains: that concern relates a tenant to
     * stancl's `domains` table, which we dropped in step 1 because resolution
     * is against `clinics.subdomain` — one column, one source of truth. Pulling
     * the trait in would advertise a relationship whose table does not exist.
     */
    use HasDatabase;

    protected $table = 'clinics';

    /**
     * Real columns. Mirrors database/migrations/central/*_create_clinics_table.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'subdomain',
            'tenancy_db_name',
            'status',
            'plan',
            'owner_name',
            'owner_email',
            'owner_phone',
            'trial_ends_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    public function planDetails(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan', 'slug');
    }

    /**
     * The clinic's own address. Derived rather than stored: two sources of
     * truth for where a clinic lives is one more than can be kept in step.
     */
    public function url(): string
    {
        return 'https://'.$this->subdomain.'.'.config('tenancy.root_domain');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true);
    }

    /** Read-only lockout for non-payment — never deletion. See B1. */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
