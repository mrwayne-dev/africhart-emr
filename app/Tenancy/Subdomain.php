<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Support\Str;

/**
 * Everything the system knows about what makes a valid clinic address.
 *
 * One place, because the answer is needed in at least four: sign-up validation,
 * the Clinic model's own guard, provisioning, and the live preview on the
 * marketing sign-up form. Four copies of "what counts as a subdomain" is four
 * chances for the preview to promise an address the system will later refuse.
 */
final class Subdomain
{
    /**
     * DNS caps a label at 63 characters. The form's preview slices at 40, so
     * that is what we accept — a limit the user can see being applied beats one
     * they discover after submitting.
     */
    public const MAX_LENGTH = 40;

    public const MIN_LENGTH = 2;

    /**
     * Derive a subdomain from a clinic's name.
     *
     * ⚠️ This MUST stay in step with the JavaScript in
     * resources/views/marketing/signup.blade.php, which shows the visitor what
     * their address will be as they type. The two cannot share an
     * implementation, so they are cross-checked by a test instead — if they
     * drift, the preview lies about the address the clinic is going to get.
     */
    public static function from(string $name): string
    {
        $slug = Str::lower($name);
        $slug = Str::ascii($slug);                       // strip accents, as the JS NFD-normalises
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim((string) $slug, '-');
        $slug = Str::substr($slug, 0, self::MAX_LENGTH);

        /*
         * Trimmed AGAIN after truncating. Cutting a long name at 40 characters
         * can land exactly on a hyphen, and "grace-medical-centre-port-" is not
         * a valid hostname label — it would be rejected by isWellFormed() with
         * a message about letters and numbers, which is no help at all to
         * someone whose clinic simply has a long name.
         */
        return trim($slug, '-');
    }

    /**
     * Labels no clinic may occupy.
     *
     * The configured list, PLUS every label that already appears in front of a
     * central domain. That derivation is deliberate: `central_domains` is itself
     * derived from `root_domain` precisely because the two drifted once and left
     * tenant routing unreachable (see config/tenancy.php). A hardcoded reserved
     * list sitting beside a derived central list would reintroduce the same
     * class of drift — add a central `status.` host and nothing would stop a
     * clinic claiming `status`.
     *
     * @return list<string>
     */
    public static function reserved(): array
    {
        $configured = array_map(
            static fn ($value) => Str::lower((string) $value),
            (array) config('tenancy.reserved_subdomains', []),
        );

        $root = (string) config('tenancy.root_domain');

        $fromCentral = [];

        foreach ((array) config('tenancy.central_domains', []) as $domain) {
            $domain = Str::lower((string) $domain);

            // Only a label sitting directly under the root domain contributes,
            // e.g. "admin.africhartemr.com" reserves "admin". The bare root
            // domain and unrelated hosts (127.0.0.1, the .test dev domain)
            // contribute nothing.
            if ($root !== '' && Str::endsWith($domain, '.'.$root)) {
                $label = Str::before($domain, '.'.$root);

                if ($label !== '' && ! Str::contains($label, '.')) {
                    $fromCentral[] = $label;
                }
            }
        }

        return array_values(array_unique(array_merge($configured, $fromCentral)));
    }

    public static function isReserved(string $subdomain): bool
    {
        return in_array(Str::lower(trim($subdomain)), self::reserved(), true);
    }

    /**
     * A syntactically usable DNS label: lowercase alphanumerics and internal
     * hyphens only.
     */
    public static function isWellFormed(string $subdomain): bool
    {
        return (bool) preg_match(
            '/^[a-z0-9](?:[a-z0-9-]{'.(self::MIN_LENGTH - 2).','.(self::MAX_LENGTH - 2).'}[a-z0-9])?$/',
            $subdomain,
        ) && Str::length($subdomain) >= self::MIN_LENGTH;
    }
}
