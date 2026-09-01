<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One clinic's own configuration. Lives in that clinic's database.
 *
 * Read through the static helpers rather than the model: they memoise per
 * request, so a value read three times while rendering an invoice costs one
 * query.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /** Known keys, so a typo in one caller is not a silent null. */
    public const CONSULTATION_FEE = 'consultation_fee';

    public const CLINIC_ADDRESS = 'clinic_address';

    public const CLINIC_PHONE = 'clinic_phone';

    public const CLINIC_EMAIL = 'clinic_email';

    /**
     * The clinic's own timezone.
     *
     * Storage stays UTC — this is for deciding what "today" MEANS to a clinic.
     * Dashboard counts and the daily queue numbering use whereDate(...,
     * today()), and today() is the UTC day: a patient checked in at 00:30 WAT
     * falls out of "today's queue" an hour later, and the daily queue_number
     * sequence can reset mid-night. Africa/Lagos is the sane default and the
     * only one any current clinic needs.
     */
    public const CLINIC_TIMEZONE = 'clinic_timezone';

    public const DEFAULT_TIMEZONE = 'Africa/Lagos';

    /**
     * Per-request memo, keyed by TENANT.
     *
     * The tenant key is part of the cache key and that is not decoration: one
     * process routinely serves several clinics — the isolation suite, any
     * `tenants:run` command, a queue worker moving between jobs. A memo keyed
     * only by setting name would hand clinic B whichever value clinic A read
     * first, which is precisely the class of bug the cache bootstrapper had to
     * be written to fix.
     *
     * @var array<string, array<string, string|null>>
     */
    protected static array $memo = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $scope = self::scope();

        if (! array_key_exists($scope, self::$memo)) {
            self::$memo[$scope] = self::query()->pluck('value', 'key')->all();
        }

        return self::$memo[$scope][$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);

        unset(self::$memo[self::scope()]);
    }

    /** Drop the memo — call after writing settings outside put(). */
    public static function forgetCached(): void
    {
        self::$memo = [];
    }

    private static function scope(): string
    {
        return (string) (tenant('id') ?? 'central');
    }
}
