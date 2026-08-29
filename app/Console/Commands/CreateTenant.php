<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\Setting;
use App\Tenancy\Subdomain;
use Database\Seeders\MedicationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Provision a clinic end to end.
 *
 * ── What this command is, and is not ───────────────────────────────────────
 *
 * It is ASSEMBLY. Every step below already worked before this command existed:
 * `Clinic::create()` fires TenantCreated, whose job pipeline creates the
 * database and runs the tenant migrations synchronously; the reserved-subdomain
 * blocklist is enforced by the model's own saving hooks; `clinic:invite` issues
 * the first admin's invitation. What was missing was a single door that does
 * them in the right order, refuses to start when it cannot finish, and cleans
 * up after itself when a step fails halfway.
 *
 * ── The failure this exists to prevent ─────────────────────────────────────
 *
 * Measured before it was written, by installing a throwing tenant migration and
 * calling Clinic::create(): the registry row was committed, the database was
 * created, migrations ran partway, the exception surfaced — and left a clinic
 * that existed in the registry, pointed at a broken database, and had taken its
 * subdomain permanently. Re-provisioning it failed on the unique index. Three
 * failure modes from one bad migration, none of them cleaned up.
 *
 * A provisioning command that half-fails and strands a clinic is worse than no
 * provisioning command, because the wreckage needs someone who knows the schema
 * to clear it.
 *
 * ── Why the rollback is "compensating deletion" and not a transaction ──────
 *
 * It cannot be atomic, and pretending otherwise would be the lie. Two reasons,
 * both structural:
 *
 *   1. stancl fires TenantCreated AFTER the model is saved, so by the time the
 *      database is being built the registry row is already committed. There is
 *      no open transaction left to roll back.
 *   2. MySQL DDL is not transactional. A migration failing on file 12 of 18
 *      leaves the first eleven tables in place; nothing rewinds them.
 *
 * So recovery is a compensating action — delete what was made — not a rollback
 * in the database sense. `$clinic->delete()` fires TenantDeleted, whose pipeline
 * drops the database; verified directly rather than assumed. When even that
 * fails, the command prints the exact manual cleanup instead of implying the
 * environment is clean.
 */
class CreateTenant extends Command
{
    protected $signature = 'tenant:create
                            {name : The clinic\'s display name}
                            {subdomain? : Its subdomain (derived from the name if omitted)}
                            {--prefix= : Identifier prefix for patient/invoice numbers, 2-12 uppercase alphanumerics}
                            {--owner-email= : The first admin\'s email — they receive the invitation}
                            {--owner-name= : The first admin\'s name}
                            {--owner-phone= : Contact phone for the clinic owner}
                            {--plan=clinic : Plan slug}
                            {--status=trialing : Lifecycle status}
                            {--fee= : Consultation fee in naira}
                            {--address= : Clinic address, printed on invoices}
                            {--phone= : Clinic phone, printed on invoices}
                            {--email= : Clinic email, printed on invoices}
                            {--no-mail : Print the invitation link without emailing it}
                            {--no-invite : Provision without issuing the first admin invitation}';

    protected $description = 'Provision a clinic: registry, database, migrations, starter data and its first admin';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $subdomain = Str::lower(trim((string) ($this->argument('subdomain') ?: Subdomain::from($name))));
        $prefix = Str::upper(trim((string) ($this->option('prefix') ?: $this->derivePrefix($name))));
        $ownerEmail = trim((string) $this->option('owner-email'));

        /*
         * Everything below happens BEFORE the first write.
         *
         * The model's saving hooks already refuse a reserved or malformed
         * subdomain, so this is not the only guard — but it is the one that
         * fires at the right TIME. Letting the model catch it means throwing
         * from inside the TenantCreated pipeline, which is the mid-flight
         * failure this command exists to avoid. Refusing up front costs one
         * function call and leaves nothing to clean up.
         */
        if ($problems = $this->preflight($name, $subdomain, $prefix, $ownerEmail)) {
            $this->newLine();
            $this->error('Cannot provision this clinic:');

            foreach ($problems as $problem) {
                $this->line('  • '.$problem);
            }

            $this->newLine();

            return self::FAILURE;
        }

        $this->line("Provisioning <info>{$name}</info> at <info>{$subdomain}</info> (prefix {$prefix})");
        $this->newLine();

        /*
         * A tenant id we choose rather than one stancl generates, so the target
         * database name is known before anything is written and can be checked
         * for an orphan left by an earlier failed run.
         */
        $tenantId = (string) Str::uuid();
        $clinic = null;

        try {
            $clinic = Clinic::create([
                'id' => $tenantId,
                'name' => $name,
                'subdomain' => $subdomain,
                'id_prefix' => $prefix,
                'plan' => (string) $this->option('plan'),

                /*
                 * status is recorded; trial_ends_at is deliberately left null.
                 * Trial mechanics — setup fee first, then 30 days gated on daily
                 * usage — are B1's to own. Provisioning noting "this is a trial"
                 * is a fact; provisioning inventing how long the trial lasts
                 * would be a billing policy decided in the wrong place.
                 */
                'status' => (string) $this->option('status'),
                'owner_name' => (string) $this->option('owner-name'),
                'owner_email' => $ownerEmail,
                'owner_phone' => $this->option('owner-phone') ?: null,
            ]);

            $this->line('  ✓ registry row created');
            $this->line('  ✓ database '.$clinic->tenancy_db_name.' created and migrated');

            $this->seedRealClinicData($clinic);
        } catch (Throwable $e) {
            return $this->compensate($clinic, $tenantId, $subdomain, $e);
        }

        /*
         * The invitation is deliberately OUTSIDE the compensation scope.
         *
         * By this point the clinic is provisioned and healthy. An invitation
         * that fails — a bounced email, an address already used — leaves a good
         * clinic that simply has no admin yet, and that is recoverable in one
         * command. Destroying a correctly built clinic because mail was
         * misconfigured would turn a small problem into data loss.
         */
        $invited = $this->option('no-invite') ? null : $this->inviteFirstAdmin($clinic, $ownerEmail);

        $this->summarise($clinic, $ownerEmail, $invited);

        /*
         * A distinct exit code for "clinic built, invitation not sent".
         *
         * Exiting 0 would tell a script everything finished, and the one thing
         * that did not — the only step that lets anybody actually sign in —
         * would pass silently. That is the shape of failure this project keeps
         * finding, so it does not get to happen here.
         *
         * Not FAILURE either: 1 invites a caller to treat the clinic as broken
         * and tear it down, and it is not broken. 2 means "look at the output",
         * and the output says exactly what to run. Documented in
         * docs/PROVISIONING.md.
         */
        return $invited === false ? self::INVALID : self::SUCCESS;
    }

    /**
     * Every reason this clinic cannot be provisioned, gathered before any write.
     *
     * All of them at once rather than one per run: an operator fixing a typo
     * only to be told about the prefix on the next attempt learns to distrust
     * the command.
     *
     * @return array<int, string>
     */
    private function preflight(string $name, string $subdomain, string $prefix, string $ownerEmail): array
    {
        $problems = [];

        if ($name === '') {
            $problems[] = 'The clinic needs a name.';
        }

        if (! Subdomain::isWellFormed($subdomain)) {
            $problems[] = "[{$subdomain}] is not a usable hostname label. Use "
                .Subdomain::MIN_LENGTH.'-'.Subdomain::MAX_LENGTH
                .' characters: lowercase letters, digits and internal hyphens.';
        } elseif (Subdomain::isReserved($subdomain)) {
            $problems[] = "[{$subdomain}] is reserved and cannot be assigned to a clinic.";
        }

        // Refusal, not convergence. Provisioning twice is an operator mistake,
        // and the right answer is to say so by name — not to attempt the insert
        // and surface a raw duplicate-key error from the unique index.
        if ($existing = Clinic::where('subdomain', $subdomain)->first()) {
            $problems[] = "[{$subdomain}] already belongs to \"{$existing->name}\" "
                ."(created {$existing->created_at?->toDateString()}). "
                .'To re-provision it, remove that clinic first — see docs/PROVISIONING.md.';
        }

        if (! preg_match('/^[A-Z0-9]{2,12}$/', $prefix)) {
            $problems[] = "The identifier prefix [{$prefix}] must be 2-12 uppercase letters or digits. "
                .'Pass --prefix= to set one explicitly.';
        } elseif ($clash = Clinic::where('id_prefix', $prefix)->first()) {
            $problems[] = "The identifier prefix [{$prefix}] is already used by \"{$clash->name}\". "
                .'Prefixes must be distinct — they appear on patient IDs and invoices. Pass --prefix=.';
        }

        /*
         * owner_name and owner_email are NOT NULL on `clinics`, so these are
         * required whether or not an invitation is issued — --no-invite skips
         * sending the invite, not recording who owns the clinic. Caught here
         * rather than as a 1048 from MySQL halfway through provisioning.
         */
        if (trim((string) $this->option('owner-name')) === '') {
            $problems[] = 'An owner name is required. Pass --owner-name=.';
        }

        if ($ownerEmail === '') {
            $problems[] = 'An owner email is required — it is recorded on the clinic and, unless '
                .'--no-invite is given, it is who receives the first admin invitation. Pass --owner-email=.';
        } elseif (! filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $problems[] = "[{$ownerEmail}] is not a valid email address.";
        }

        foreach ($this->orphanedDatabases() as $orphan) {
            $problems[] = "An orphaned tenant database [{$orphan}] exists with no clinic in the registry — "
                .'the wreckage of an earlier failed provision. Drop it before continuing: '
                ."DROP DATABASE `{$orphan}`;";
        }

        return $problems;
    }

    /**
     * Tenant databases with no clinic pointing at them.
     *
     * This is what "check the target database does not already exist" means in
     * practice. Names are derived from a fresh uuid, so a direct collision is
     * not the real risk; the real risk is a database left behind by a run that
     * failed before this command existed to clean up. Blocking on it is
     * deliberate — provisioning onto a box with known wreckage should stop and
     * make somebody look.
     *
     * @return array<int, string>
     */
    private function orphanedDatabases(): array
    {
        $prefix = (string) config('tenancy.database.prefix');
        $suffix = (string) config('tenancy.database.suffix');

        if ($prefix === '') {
            return [];
        }

        $known = Clinic::pluck('tenancy_db_name')->filter()->all();

        $found = DB::connection(config('tenancy.database.central_connection'))
            ->select(
                'SELECT schema_name AS name FROM information_schema.schemata WHERE schema_name LIKE ?',
                [$prefix.'%'.$suffix],
            );

        return array_values(array_diff(array_column($found, 'name'), $known));
    }

    /**
     * Seed what a REAL clinic should have, and nothing else.
     *
     * Explicitly NOT TenantDatabaseSeeder. That calls StaffSeeder, PatientSeeder
     * and Phase1DemoSeeder — invented staff logins and fictional patients, for
     * development and for the throwaway tenants the isolation suite provisions.
     * Running it for a real clinic would put invented people in a medical
     * record, which is why the seeder is commented out of the TenantCreated
     * pipeline and must stay that way.
     *
     * MedicationSeeder is the exception and belongs here: a starter drug
     * catalogue with prices the clinic then adjusts is reference data, not
     * fiction, and it is idempotent (updateOrCreate).
     */
    private function seedRealClinicData(Clinic $clinic): void
    {
        $clinic->run(function () use ($clinic) {
            app(MedicationSeeder::class)->run();

            $this->line('  ✓ starter medication catalogue seeded');

            $settings = array_filter([
                Setting::CONSULTATION_FEE => $this->option('fee'),
                Setting::CLINIC_ADDRESS => $this->option('address'),
                Setting::CLINIC_PHONE => $this->option('phone'),
                Setting::CLINIC_EMAIL => $this->option('email') ?: $clinic->owner_email,
            ], fn ($value) => $value !== null && $value !== '');

            foreach ($settings as $key => $value) {
                Setting::put($key, $value);
            }

            if ($settings) {
                $this->line('  ✓ clinic settings written: '.implode(', ', array_keys($settings)));
            }
        });
    }

    /**
     * Hand the first admin their way in, by calling the command that already
     * does this rather than a second copy of the invitation logic.
     */
    private function inviteFirstAdmin(Clinic $clinic, string $ownerEmail): bool
    {
        $this->newLine();

        /*
         * A THROWN invitation error must be caught here, not allowed to escape.
         *
         * By this point the clinic is built. Letting an exception propagate out
         * of handle() would abort with a stack trace and no summary — and the
         * operator would be left staring at a crash, with a perfectly healthy
         * clinic they have every reason to assume is broken. The whole point of
         * keeping the invitation outside the compensation scope is that its
         * failure is survivable, which is only true if it is survived here.
         */
        try {
            $exit = Artisan::call('clinic:invite', array_filter([
                'subdomain' => $clinic->subdomain,
                'email' => $ownerEmail,
                '--role' => 'admin',
                '--name' => $this->option('owner-name') ?: null,
                '--no-mail' => (bool) $this->option('no-mail'),
            ]));

            $this->line(trim(Artisan::output()));

            return $exit === self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('The invitation could not be issued: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Undo what this run created. See the class docblock for why this is
     * compensation rather than a transaction.
     */
    private function compensate(?Clinic $clinic, string $tenantId, string $subdomain, Throwable $failure): int
    {
        $this->newLine();
        $this->error('Provisioning failed: '.$failure->getMessage());
        $this->newLine();

        /*
         * Do NOT trust the local variable.
         *
         * `Clinic::create()` throws from inside the TenantCreated pipeline —
         * after the row is committed and the database is built — so the
         * assignment never completes and $clinic is still null in the catch.
         * An earlier version of this method believed it and printed "nothing to
         * undo" while leaving a registry row AND a fully created database
         * behind: the command's own report of a clean rollback was false, which
         * is the precise failure mode it was written to prevent, reproduced
         * inside the prevention.
         *
         * So the wreckage is looked for, not inferred. The tenant id is chosen
         * up front for exactly this reason.
         */
        $clinic ??= Clinic::find($tenantId) ?? Clinic::where('subdomain', $subdomain)->first();

        $expectedDatabase = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix');

        if (! $clinic || ! $clinic->exists) {
            // No row — but the database can still outlive it, so look before
            // claiming the environment is clean.
            if ($this->databaseExists($expectedDatabase)) {
                $this->manualCleanup($subdomain, $expectedDatabase, true, false);

                return self::FAILURE;
            }

            $this->info('Nothing to undo — the failure happened before anything was created.');
            $this->line("The subdomain [{$subdomain}] is still free.");

            return self::FAILURE;
        }

        $database = (string) ($clinic->tenancy_db_name ?: $expectedDatabase);

        try {
            // Fires TenantDeleted, whose pipeline drops the database.
            $clinic->delete();

            // Verified, not assumed: the point of the exercise is that nothing
            // is left behind, and only looking proves it.
            $rowGone = ! Clinic::where('subdomain', $subdomain)->exists();
            $dbGone = $database === '' || ! $this->databaseExists($database);

            if ($rowGone && $dbGone) {
                $this->info('Rolled back cleanly. Nothing was left behind:');
                $this->line('  ✓ registry row removed');
                $this->line("  ✓ database {$database} dropped");
                $this->line("  ✓ subdomain [{$subdomain}] is free again and can be re-provisioned");

                return self::FAILURE;
            }

            $this->manualCleanup($subdomain, $database, $rowGone, $dbGone);

            return self::FAILURE;
        } catch (Throwable $rollbackFailure) {
            $this->error('The rollback ITSELF failed: '.$rollbackFailure->getMessage());

            $this->manualCleanup(
                $subdomain,
                $database ?: config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix'),
                ! Clinic::where('subdomain', $subdomain)->exists(),
                false,
            );

            return self::FAILURE;
        }
    }

    /**
     * Say exactly what is still there and exactly how to remove it.
     *
     * The operator reading this has a half-provisioned clinic and no reason to
     * trust the tool that made it, so vagueness here is worse than useless.
     */
    private function manualCleanup(string $subdomain, string $database, bool $rowGone, bool $dbGone): void
    {
        $this->newLine();
        $this->error('MANUAL CLEANUP REQUIRED — this clinic is half-created.');
        $this->newLine();

        if (! $rowGone) {
            $this->line('  The registry row still exists. Remove it with:');
            $this->line("      php artisan tinker --execute=\"App\\Models\\Clinic::where('subdomain','{$subdomain}')->first()?->delete();\"");
            $this->newLine();
        }

        if (! $dbGone && $database !== '') {
            $this->line('  The tenant database still exists. Drop it with:');
            $this->line("      DROP DATABASE `{$database}`;");
            $this->newLine();
        }

        $this->line("  Until both are gone, [{$subdomain}] cannot be re-provisioned.");
        $this->newLine();
    }

    private function databaseExists(string $database): bool
    {
        return (bool) DB::connection(config('tenancy.database.central_connection'))
            ->select('SELECT 1 FROM information_schema.schemata WHERE schema_name = ?', [$database]);
    }

    /**
     * A starting prefix from the clinic's name: the first four alphanumerics,
     * uppercased. Only a suggestion — preflight rejects it if it collides, and
     * --prefix overrides it. It appears on every patient ID and invoice, so it
     * is worth an operator's attention rather than silent generation.
     */
    private function derivePrefix(string $name): string
    {
        return Str::upper(Str::substr((string) preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));
    }

    private function summarise(Clinic $clinic, string $ownerEmail, ?bool $invited): void
    {
        $this->newLine();
        $this->info("Provisioned {$clinic->name}.");
        $this->newLine();
        $this->line('  Address    '.$clinic->url());
        $this->line('  Database   '.$clinic->tenancy_db_name);
        $this->line('  Prefix     '.$clinic->idPrefix().'  (patient IDs, invoice numbers)');
        $this->line('  Status     '.$clinic->status);

        if ($invited === false) {
            /*
             * The clinic is FINE. Say so first and say it plainly — an operator
             * who reads a failure here and concludes the clinic is broken may
             * tear down something that is working perfectly.
             */
            $this->newLine();
            $this->warn('The clinic is provisioned and healthy. Only the admin invitation failed.');
            $this->line('Do NOT tear this clinic down. Complete it by running:');
            $this->newLine();
            $this->line("    php artisan clinic:invite {$clinic->subdomain} {$ownerEmail} --role=admin");
            $this->newLine();

            return;
        }

        if ($invited === null) {
            $this->newLine();
            $this->comment('No invitation was issued (--no-invite). Nobody can sign in yet. When ready:');
            $this->line("    php artisan clinic:invite {$clinic->subdomain} <email> --role=admin");
        }

        $this->newLine();
    }
}
