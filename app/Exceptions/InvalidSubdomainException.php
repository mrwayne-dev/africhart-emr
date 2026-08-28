<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A clinic was about to be given an address it may not have.
 *
 * An exception rather than a validation failure because this fires at the model
 * layer, below any form. By the time it throws, something has already bypassed
 * (or never had) validation — a seeder, an artisan command, a console session,
 * a future provisioning path. Refusing loudly is the point: the alternative is
 * a clinic silently occupying `api` and nobody noticing until the day that
 * hostname is needed.
 */
class InvalidSubdomainException extends RuntimeException
{
    public static function reserved(string $subdomain): self
    {
        return new self(
            "The subdomain [{$subdomain}] is reserved and cannot be assigned to a clinic. "
            .'Reserved labels are listed in config/tenancy.reserved_subdomains and derived '
            .'from tenancy.central_domains.'
        );
    }

    public static function malformed(string $subdomain): self
    {
        return new self(
            "The subdomain [{$subdomain}] is not a usable hostname label. Use 2-40 characters: "
            .'lowercase letters, digits and internal hyphens only.'
        );
    }
}
