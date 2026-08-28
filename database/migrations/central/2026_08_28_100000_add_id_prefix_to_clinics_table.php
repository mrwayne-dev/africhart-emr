<?php

use App\Models\Clinic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * CENTRAL — each clinic's own identifier prefix.
 *
 * ── Why this is central, and not in the tenant `settings` table ────────────
 *
 * ARCHITECTURE §4.2 lists "ID prefix" among the tenant settings, and that is
 * the wrong home for it. The entire point of this change is that two clinics
 * must not mint the same identifier — verified before the fix, where Hope and
 * Grace both generated ACH-20260828-0001 on the same day.
 *
 * A per-tenant setting cannot express that. Nothing in clinic A's database can
 * see clinic B's, so two clinics could both choose `ACH` and we would have
 * replaced a guaranteed collision with a hoped-for absence of one. Central is
 * the only place a UNIQUE index can actually enforce distinctness — the same
 * argument that put invitations in the tenant database, run the other way:
 * store the fact where the constraint you need can exist.
 *
 * The consultation fee and the clinic's contact details stay in tenant
 * `settings`, because nothing about them needs to be unique across clinics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            // Nullable for the moment so existing rows survive the add; made
            // NOT NULL below, once every clinic has one.
            $table->string('id_prefix', 12)->nullable()->after('subdomain');
        });

        $this->backfillExistingClinics();

        Schema::table('clinics', function (Blueprint $table) {
            $table->string('id_prefix', 12)->nullable(false)->change();
            $table->unique('id_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropUnique(['id_prefix']);
            $table->dropColumn('id_prefix');
        });
    }

    /**
     * Give every existing clinic a distinct prefix derived from its subdomain.
     *
     * Subdomains are already unique, but their first few letters are not
     * ("grace-medical" and "grace-clinic" both start GRAC), so a collision is
     * resolved by counting up rather than assumed away.
     *
     * Uses the query builder, not the Clinic model: the model carries a
     * `saving` guard and stancl's virtual-column behaviour, neither of which
     * should run during a schema migration.
     */
    private function backfillExistingClinics(): void
    {
        $taken = [];

        foreach (DB::table('clinics')->select('id', 'subdomain')->get() as $clinic) {
            $base = Str::upper(Str::substr(preg_replace('/[^a-z0-9]/', '', (string) $clinic->subdomain), 0, 4));

            if ($base === '') {
                $base = 'CLINIC';
            }

            $candidate = $base;
            $suffix = 2;

            while (in_array($candidate, $taken, true)) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            $taken[] = $candidate;

            DB::table('clinics')->where('id', $clinic->id)->update(['id_prefix' => $candidate]);
        }
    }
};
