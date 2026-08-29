# Backups & Operations

Operational runbook for an AfriChart EMR clinic deployment: the background worker,
the scheduler, automated backups, and the **rehearsed restore**.

> These three cron-driven pieces (worker, scheduler, backups) are required in
> production. Without the worker, verification emails never send. Without the
> scheduler, backups never run.

---

## 1. Queue worker (required)

Mail (email verification codes, admin activity, backup notifications) is queued so
front-desk actions return instantly instead of waiting on SMTP. A worker must be
running to actually deliver it.

`.env`:

```
QUEUE_CONNECTION=database
```

(The `jobs` table already exists via migration. Use `sync` only in local dev when
you don't want a worker.)

**VPS (preferred) — Supervisor:**

```ini
[program:africhart-worker]
command=php /path/to/africhart-emr/artisan queue:work --tries=3 --backoff=10 --max-time=3600
autostart=true
autorestart=true
user=youruser
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/africhart-emr/storage/logs/worker.log
stopwaitsecs=3600
```

**cPanel / shared (no Supervisor) — cron every minute:**

```
* * * * * cd /path/to/africhart-emr && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

After deploying code that changes a queued job, restart the worker: `php artisan queue:restart`.

---

## 2. Scheduler (required for backups)

Laravel's scheduler runs everything in `routes/console.php`. Wire the single entry
to cron:

```
* * * * * cd /path/to/africhart-emr && php artisan schedule:run >> /dev/null 2>&1
```

**Without this line nothing below runs, and nothing complains.** That is not
hypothetical: before Sprint 0 no cron entry existed, so `schedule:run` had never
executed once, and "automated backups" sat on the done list while being
operationally dead. See §5.

Scheduled jobs (see `routes/console.php`):

| Time  | Command                  | Scope       | Purpose                                        |
|-------|--------------------------|-------------|------------------------------------------------|
| 01:30 | `tenants:backup-clean`   | per clinic  | Prune each clinic's archives past retention    |
| 02:00 | `tenants:backup`         | per clinic  | One encrypted archive per clinic               |
| 03:00 | `tenants:backup-monitor` | per clinic  | Assert each clinic has a recent archive        |
| 06:00 | `clinics:audit-trials`   | central     | Report on trials from the registry             |
| 07:00 | `schedule:audit`         | central     | Alert on any task that has gone silent         |

**Do not substitute spatie's own `backup:run`, `backup:clean` or `backup:monitor`
for these.** Each of them acts on whatever database and disk the *default*
connection points at, which under tenancy is the central one — every time. They
complete, report success, and touch no clinic's data. All three commands above
exist solely to iterate clinics explicitly. Two of the three have already shipped
this bug and been fixed; the docs previously listed the spatie commands here,
which is how it stayed invisible.

---

## 3. Backups (spatie/laravel-backup)

One archive **per clinic**, containing that clinic's database dump. The app code is
in git and deliberately excluded to keep archives small.

Archives are written per tenant, because stancl's filesystem bootstrapper suffixes
`storage_path()` during tenancy:

```
storage/tenant<clinic-uuid>/app/<APP_NAME>/<subdomain>-<timestamp>.zip
```

Restoring one clinic therefore never means touching another's data.

### Encryption (set this)

```
BACKUP_ARCHIVE_PASSWORD=<long random value>
```

**Blank disables encryption silently** — spatie treats a null password as "no
encryption", with no warning. Archives are AES-256 when it is set.

> If this value is lost, every archive encrypted with it is unrecoverable. Store it
> wherever the MySQL root password lives, not only in `.env`.

⚠️ **`unzip` cannot read these archives.** Info-ZIP does not support WinZip AES, so
it fails in a way that looks like a corrupt file. Use `7z`. (An early encryption
check on this project "passed" only because `unzip` was not installed at all.)

### Off-site copies — DEFERRED, not currently configured

**There is no off-site copy today. Backups live only on the application server, so
losing that server loses the backups with it.** This is a known, accepted gap.

`BACKUP_OFFSITE_DISK` is blank and no destination is wired. The plan is a free
destination (Google Drive via `rclone`) rather than paid object storage; it is not
built yet. Do not read the `AWS_*` keys in `.env.example` as evidence of an off-site
copy — they are placeholders.

Two pieces of groundwork are already in place for whenever a destination is added:
`league/flysystem-aws-s3-v3` is installed, and `s3` is listed in
`config/tenancy.php` under `filesystem.disks`, so any S3-compatible destination
gets a per-clinic key prefix instead of every clinic sharing one.

### Manual run / verify

```
php artisan tenants:backup                      # every clinic
php artisan tenants:backup --clinic=<subdomain> # one clinic
php artisan tenants:backup-monitor              # is every clinic's archive recent?
php artisan tenants:backup-clean                # apply retention, per clinic
```

`mysqldump` must be on the server `PATH`. If it lives elsewhere, set the dump binary
path under the connection's `dump` key in `config/database.php`.

---

## 4. Tested restore (do this — a backup you've never restored doesn't count)

Rehearse into a **scratch** database, never the live one. `<uuid>` is the clinic's
tenant id; the dump inside the archive is named after the tenant database.

```bash
# 1. Decrypt and extract. 7z, NOT unzip — see the AES note in §3.
7z x -p"$BACKUP_ARCHIVE_PASSWORD" <archive>.zip -o/tmp/restore-test

# 2. Load into a throwaway database.
mysql -u root -p -e "CREATE DATABASE africhart_restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p africhart_restore_test < /tmp/restore-test/db-dumps/mysql-africhart_tenant_<uuid>.sql

# 3. Verify CONTENTS, not the exit code. Row counts, and that the clinic's own
#    ID prefix is present while no other clinic's is.
mysql -u root -p africhart_restore_test -e \
  "SELECT (SELECT COUNT(*) FROM patients) AS patients,
          (SELECT COUNT(*) FROM consultations) AS consultations,
          (SELECT COUNT(*) FROM invoices) AS invoices;"
mysql -u root -p africhart_restore_test -e \
  "SELECT patient_id FROM patients ORDER BY patient_id LIMIT 3;"

# 4. Drop the scratch DB when satisfied.
mysql -u root -p -e "DROP DATABASE africhart_restore_test;"
```

A stronger check, if you want certainty rather than a spot check: `CHECKSUM TABLE`
every table in the restored database and compare against the live one.

Record the date of the last successful restore drill here:

- Last verified restore: 2026-08-29 — full round trip (backup → object storage →
  retrieved → decrypted → restored), both clinics, all 20 table checksums and 185
  columns identical to live, zero cross-tenant rows.

---

## 5. Knowing it still works (silence detection)

Every scheduled task records its run in `scheduled_task_runs`. `schedule:audit`
asserts a **positive** — that each task has succeeded within the last 26 hours —
and treats an absent row as the alarm. Monitoring only failures is what let a
scheduler that had never fired look healthy for months.

Two layers, and they answer different questions:

| Check                    | Question it asks                              |
|--------------------------|-----------------------------------------------|
| `schedule:audit`         | Did the task **report** success recently?     |
| `tenants:backup-monitor` | Does a recent archive **actually exist**?     |

They fail apart, which is the point: a backup run that reports ✓ while writing
nothing satisfies the first and fails the second. `tenants:backup-monitor` is
itself listed in `schedule:audit`'s expectations, so a monitor that stops running
is detected rather than assumed healthy.

**The outermost layer is `/up`.** The health endpoint runs the same silence check
and returns 500 when any expected task has gone quiet, so an external uptime
monitor becomes the dead-man's switch — it lives outside the process that could
die.

⚠️ **Arming it is a deployment step.** Until something actually polls `/up`, that
outermost layer is not connected.
