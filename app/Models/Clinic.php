<?php

namespace App\Models;

use App\Exceptions\InvalidSubdomainException;
use App\Tenancy\Subdomain;
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
            'id_prefix',
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

    /**
     * The reserved-subdomain blocklist, enforced where it cannot be skipped.
     *
     * ARCHITECTURE §3.3 asks for this in sign-up validation AND at provisioning,
     * and is explicit that the second is what actually protects the system
     * "since the first can be bypassed". This is that second layer.
     *
     * It lives on the model rather than in the provisioning command because
     * `tenant:create` is not the only way a clinic is born: seeders, tinker, the
     * isolation suite and every future admin screen all go through
     * Clinic::create(). A check in the command would guard one door in a
     * building with several — the same reasoning that put staff_invitations in
     * the tenant database instead of writing an ownership comparison.
     *
     * `saving`, not `creating`: renaming an existing clinic onto a reserved
     * label is the same mistake arriving later, and it would be worse — the
     * clinic already has staff, records and a bookmarked address.
     *
     * Verified before this existed: a clinic claiming `api` provisioned cleanly,
     * database and all, with `api` sitting in the reserved list the whole time.
     */
    protected static function booted(): void
    {
        static::saving(function (self $clinic) {
            if (! $clinic->isDirty('subdomain')) {
                return;
            }

            $subdomain = (string) $clinic->subdomain;

            if (! Subdomain::isWellFormed($subdomain)) {
                throw InvalidSubdomainException::malformed($subdomain);
            }

            if (Subdomain::isReserved($subdomain)) {
                throw InvalidSubdomainException::reserved($subdomain);
            }
        });

        /*
         * The identifier prefix is what stops two clinics minting the same
         * patient and invoice numbers, so it must actually be present and
         * usable. The UNIQUE index enforces distinctness; this enforces shape,
         * because an empty or lowercase prefix would produce identifiers that
         * look like a bug on a document a patient keeps.
         */
        static::saving(function (self $clinic) {
            if (! $clinic->isDirty('id_prefix')) {
                return;
            }

            $prefix = (string) $clinic->id_prefix;

            if (! preg_match('/^[A-Z0-9]{2,12}$/', $prefix)) {
                throw new \InvalidArgumentException(
                    "The clinic identifier prefix [{$prefix}] must be 2-12 uppercase letters or digits. "
                    .'It appears on patient IDs and invoice numbers.'
                );
            }
        });
    }

    /**
     * The prefix this clinic's patient, consultation and invoice identifiers
     * carry. Falls back to the old global value ONLY so that a half-migrated
     * environment fails visibly at the unique index rather than fatally here.
     */
    public function idPrefix(): string
    {
        return (string) ($this->id_prefix ?: 'ACH');
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
