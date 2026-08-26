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
#[Fillable([
    'slug', 'name', 'blurb', 'cta_label',
    'monthly_price', 'setup_fee', 'price_basis',
    'max_doctors', 'max_sites',
    'features', 'highlights',
    'is_active', 'is_featured', 'sort_order',
])]
class Plan extends Model
{
    use CentralConnection;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'highlights' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
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

    // --- Presentation ---
    //
    // These live on the model rather than in a Blade file so that every surface
    // showing a price formats it identically. The numbers had four homes before
    // this; the formatting should not acquire several of its own.

    /** e.g. "45,000" — the figure alone, so views own the ₦ and the styling. */
    public function formattedMonthlyPrice(): string
    {
        return number_format($this->monthlyPriceNaira());
    }

    public function formattedSetupFee(): string
    {
        return number_format($this->setupFeeNaira());
    }

    /** True when the monthly price is charged for EACH location, not once. */
    public function isPerSite(): bool
    {
        return $this->price_basis === 'per_site';
    }

    /**
     * What the monthly figure is charged against — rendered next to the price.
     *
     * Group is per site, and saying only "/month" beside its number would
     * understate the cost of a three-location group by two thirds.
     */
    public function priceSuffix(): string
    {
        return $this->isPerSite() ? '/month per site' : '/month';
    }

    /** A short capacity line for the sign-up plan chip. */
    public function capacityNote(): string
    {
        // Not "per location" — priceSuffix() already says per site, and the two
        // together read as "per site, per clinic location". What is useful here
        // is the capacity the tier is FOR.
        if ($this->isPerSite()) {
            return 'Two or more locations';
        }

        return $this->max_doctors
            ? "Up to {$this->max_doctors} doctors"
            : 'Unlimited doctors';
    }
}
