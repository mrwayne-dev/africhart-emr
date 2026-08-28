<?php

declare(strict_types=1);

namespace App\Rules;

use App\Tenancy\Subdomain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the address a clinic NAME would produce.
 *
 * Applied to `clinic_name` rather than to a subdomain field, because the
 * sign-up form has no subdomain field — it derives one from the name and shows
 * it to the visitor as they type ("your clinic's address will be
 * grace-medical.africhartemr.com"). That preview is a promise, and until now
 * the form would make it about any name at all, including `api`.
 *
 * ── What this deliberately does NOT check: availability ────────────────────
 *
 * Telling a public form that a subdomain is "already taken" confirms that a
 * clinic exists at that name, to anyone who cares to ask. That is the same
 * enumeration the find-your-clinic page refuses to do, and it would be odd to
 * guard one and not the other. The reserved list carries no such risk — it is
 * static configuration, identical for every visitor, and reveals nothing about
 * who our customers are.
 *
 * Uniqueness is enforced where a collision can actually be handled: the unique
 * index on clinics.subdomain, at provisioning, by an operator who can pick
 * something else and say so.
 */
class UsableClinicSubdomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $subdomain = Subdomain::from((string) $value);

        if (! Subdomain::isWellFormed($subdomain)) {
            $fail('Please enter your clinic name using letters and numbers — we build your web address from it.');

            return;
        }

        if (Subdomain::isReserved($subdomain)) {
            $fail(
                'That name would give your clinic a web address we keep for the AfriChart system itself. '
                .'Please use your clinic\'s full name, and we will sort the address out with you.'
            );
        }
    }
}
