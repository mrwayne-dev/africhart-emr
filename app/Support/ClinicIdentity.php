<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * The clinic's own identity and its idea of "today".
 *
 * One accessor rather than four variables threaded through every controller.
 * The clinic's name is needed by the invoice, the page title of nearly every
 * screen and both dashboard headers; passing it from each controller would mean
 * every new screen remembering to, and the ones that forgot would silently show
 * the vendor's name instead — which is the bug being fixed here.
 *
 * The name comes from CENTRAL `clinics.name`, never from a tenant setting. It
 * is identity, like the subdomain and the ID prefix, and a copy in the tenant
 * database would be a second name free to drift from the one the registry
 * knows. Contact details are per-clinic preferences and live in tenant
 * settings, which is why they come from a different place.
 *
 * Memoised per tenant. Reads happen several times per request — layout title,
 * header, letterhead — and one process serves many clinics, so the tenant key
 * is part of the memo key for the same reason it is in Setting's.
 */
final class ClinicIdentity
{
    /** @var array<string, array<string, string|null>> */
    private static array $memo = [];

    public static function name(): string
    {
        return (string) (tenant('name') ?: config('app.name'));
    }

    public static function address(): ?string
    {
        return self::setting(Setting::CLINIC_ADDRESS);
    }

    public static function phone(): ?string
    {
        return self::setting(Setting::CLINIC_PHONE);
    }

    public static function email(): ?string
    {
        return self::setting(Setting::CLINIC_EMAIL);
    }

    /**
     * The clinic's timezone. Africa/Lagos unless it has said otherwise.
     *
     * Never used to decide what is STORED — storage is UTC throughout, which is
     * what the connection pin settled. This decides only what a date MEANS when
     * a clinic asks for "today".
     */
    public static function timezone(): string
    {
        $zone = self::setting(Setting::CLINIC_TIMEZONE) ?: Setting::DEFAULT_TIMEZONE;

        // A bad value must not take the app down: an unknown zone would throw
        // from every dashboard query at once.
        return in_array($zone, timezone_identifiers_list(), true)
            ? $zone
            : Setting::DEFAULT_TIMEZONE;
    }

    /**
     * The clinic's current day as a UTC half-open range: [start, end).
     *
     * This exists because `whereDate('created_at', today())` was comparing a
     * UTC-stored column against the UTC calendar day, while the clinic lives in
     * Africa/Lagos. A patient checked in at 00:30 in Lagos is stored at 23:30
     * UTC the previous day, so an hour later they fell out of "today's queue"
     * and the daily queue_number sequence could hand out a number twice.
     *
     * A range, not a date, because the two cannot be reconciled with whereDate
     * at all: the clinic's day starts partway through a UTC day, so no single
     * UTC calendar date names it.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function todayRange(): array
    {
        $start = CarbonImmutable::now(self::timezone())->startOfDay();

        return [
            $start->utc(),
            $start->addDay()->utc(),
        ];
    }

    /** The clinic's current local date — for display, never for comparison. */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone())->startOfDay();
    }

    public static function forget(): void
    {
        self::$memo = [];
    }

    private static function setting(string $key): ?string
    {
        $scope = (string) (tenant('id') ?? 'central');

        if (! array_key_exists($scope, self::$memo)) {
            self::$memo[$scope] = [];
        }

        if (! array_key_exists($key, self::$memo[$scope])) {
            $value = Setting::get($key);
            self::$memo[$scope][$key] = $value === null ? null : (string) $value;
        }

        return self::$memo[$scope][$key] ?: null;
    }
}
