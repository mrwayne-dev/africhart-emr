# Provisioning a clinic

Operator runbook for `php artisan tenant:create` — standing up a new clinic end to
end: registry record, its own database, migrations, starter data, and the first
admin's invitation.

> One command does all of it. If it cannot finish, it undoes what it started —
> see [§5 When it fails](#5-when-it-fails).

---

## 1. The command

```bash
php artisan tenant:create "Riverside Family Clinic" riverside \
    --owner-name="Ada Okafor" \
    --owner-email=ada@riverside.example \
    --fee=5000 \
    --address="12 Marina Road, Lagos" \
    --phone="+2348012345678"
```

| Argument / option | Required | Notes |
|---|---|---|
| `name` | yes | Display name. Appears on invoices and the letterhead |
| `subdomain` | no | Derived from the name if omitted. Becomes `<subdomain>.<root domain>` |
| `--owner-name` | **yes** | Recorded on the clinic (`NOT NULL`) |
| `--owner-email` | **yes** | Recorded on the clinic, and who receives the first admin invitation |
| `--prefix` | no | 2–12 uppercase alphanumerics. Derived from the name if omitted |
| `--owner-phone` | no | |
| `--plan` | no | Defaults to `clinic` |
| `--status` | no | Defaults to `trialing` |
| `--fee` | no | Consultation fee in ₦. Falls back to `config('billing.consultation_fee')` |
| `--address` `--phone` `--email` | no | The clinic's own contact details, printed on invoices |
| `--no-mail` | no | Print the invitation link without emailing it |
| `--no-invite` | no | Provision without issuing an invitation. Nobody can sign in until you issue one |

### The identifier prefix matters

It appears on every patient ID and invoice number (`RIVE-P-0001`) and it must be
unique across clinics — that uniqueness is what stops two clinics minting the same
identifiers. The derived value is only a suggestion: it is the first four
alphanumerics of the name. **Check it before accepting it.** It goes on documents
patients keep.

---

## 2. What each step does

1. **Preflight — nothing is written yet.** Validates the subdomain (well-formed,
   and not on the reserved blocklist), that it is free, that the prefix is
   well-shaped and unused, that owner details are present, and that no orphaned
   tenant database is lying around. **Every problem is reported at once**, so you
   fix them in one pass.
2. **Registry row** — inserts the clinic into the central `clinics` table.
3. **Database + migrations** — creating the row fires `TenantCreated`, whose job
   pipeline creates `africhart_tenant_<uuid>` and runs the tenant migrations.
   This is synchronous: when the command returns, it has happened.
4. **Starter data** — the medication catalogue (10 common drugs with prices the
   clinic adjusts) and any settings you passed.
5. **First admin invitation** — calls `clinic:invite`, printing the link as well
   as emailing it.

### What it deliberately does NOT seed

**No demo data. Ever.** `TenantDatabaseSeeder` (staff logins, fictional patients,
`Phase1DemoSeeder`) is for development and for the throwaway tenants the isolation
suite provisions. Seeding a real clinic with it would put invented people in a
medical record. `tenant:create` calls `MedicationSeeder` *only* — a drug catalogue
is reference data, not fiction.

If you are ever unsure whether a clinic got demo data, look rather than assume:

```bash
php artisan tinker --execute="\App\Models\Clinic::where('subdomain','<sub>')->first()->run(function(){
  echo 'patients=', \DB::table('patients')->count(), ' staff=', \DB::table('staff')->count(), PHP_EOL; });"
```

A freshly provisioned clinic has **0 patients and 0 staff**.

---

## 3. What to check afterwards

```
 Address    https://riverside.africhart-emr.test
 Database   africhart_tenant_062ef9f7-…
 Prefix     RIVE  (patient IDs, invoice numbers)
 Status     trialing
```

- **The prefix** is what you want on their documents.
- **The invitation link** is printed. It expires in 7 days and works once.
- **`trial_ends_at` is deliberately null.** Provisioning records *that* a clinic
  is trialing; it does not decide how long the trial runs. Billing (B1) owns that.

---

## 4. Exit codes

| Code | Meaning |
|---|---|
| `0` | Fully provisioned, invitation issued |
| `1` | Nothing was provisioned — refused in preflight, or rolled back. Safe to fix and retry |
| `2` | **Clinic provisioned and healthy, but the invitation was not issued.** Not a broken clinic — see §6 |

Code `2` exists so a script cannot pass over a half-finished onboarding in
silence. It never means "tear the clinic down".

---

## 5. When it fails

### It refuses before starting (exit 1)

Nothing was created. The subdomain is still free. Fix what it listed and re-run.

Re-running for a subdomain that already exists is **refused by design** — it names
the existing clinic rather than colliding on the unique index. Provisioning is not
idempotent-by-convergence: running it twice is an operator mistake, and the right
answer is to say so.

### It fails partway (exit 1, "Rolled back cleanly")

The command undoes what it made — registry row and database — and tells you the
subdomain is free again.

**This is compensating deletion, not a transaction, and the code says so.** It
cannot be atomic: the registry row is committed before the database-creation event
fires, and MySQL DDL is not transactional, so a migration failing on file 12 of 18
leaves the first eleven tables behind. What the command does is delete what it
created and then **verify** the deletion by looking — not by trusting its own
earlier step.

Confirm it yourself if you want certainty:

```bash
php artisan tinker --execute="
echo 'row: ', \App\Models\Clinic::where('subdomain','<sub>')->exists() ? 'STILL THERE' : 'gone', PHP_EOL;
\$dbs = \DB::select(\"SELECT schema_name AS s FROM information_schema.schemata WHERE schema_name LIKE 'africhart_tenant_%'\");
\$known = \App\Models\Clinic::pluck('tenancy_db_name')->all();
echo 'orphan databases: ', count(array_diff(array_column(\$dbs,'s'), \$known)), PHP_EOL;"
```

### The rollback itself fails

The command prints `MANUAL CLEANUP REQUIRED` with the exact statements to run —
the tinker line to remove the registry row, and the literal
`DROP DATABASE \`africhart_tenant_…\`;`. It does not claim the environment is
clean when it is not. **Until both are gone, that subdomain cannot be
re-provisioned** — and preflight will block the next attempt while an orphaned
database remains, which is deliberate: wreckage on the box should make somebody
look.

---

## 6. The invitation failed (exit 2)

**The clinic is fine.** Do not tear it down. Only the invitation step failed —
most often because mail is not configured on a fresh box, which is exactly where
mail is least likely to work.

The command prints the recovery line. It is just:

```bash
php artisan clinic:invite <subdomain> <owner-email> --role=admin
```

Add `--no-mail` to print the link instead of sending it, then pass it to the admin
over a channel you trust. **Nothing stores the raw token**, so the link cannot be
recovered later — if you lose it, issue a fresh invitation.

---

## 7. Removing a clinic

Deleting the clinic drops its database with it — `TenantDeleted` fires
`DeleteDatabase`. There is no undo and no confirmation prompt.

```bash
php artisan tinker --execute="\App\Models\Clinic::where('subdomain','<sub>')->first()?->delete();"
```

⚠️ **Take a backup first** unless the clinic is a throwaway:
`php artisan tenants:backup --clinic=<sub>`. See `docs/BACKUPS_AND_OPS.md`.
