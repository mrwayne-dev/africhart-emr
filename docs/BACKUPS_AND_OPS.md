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

Laravel's scheduler runs the backup commands defined in `routes/console.php`. Wire
the single Laravel scheduler entry to cron:

```
* * * * * cd /path/to/africhart-emr && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs (see `routes/console.php`):

| Time  | Command          | Purpose                                  |
|-------|------------------|------------------------------------------|
| 01:30 | `backup:clean`   | Prune backups past the retention window  |
| 02:00 | `backup:run`     | Dump the DB + `storage/app`, store it    |
| 03:00 | `backup:monitor` | Alert if backups are missing/too old     |

---

## 3. Backups (spatie/laravel-backup)

Backs up the **database** (mysqldump) plus uploaded files in `storage/app`. The app
code itself is in git and is intentionally excluded to keep archives small.

**Storage** — always a local copy; add an off-site S3-compatible destination:

```
# Backblaze B2 / Cloudflare R2 / Wasabi / AWS S3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=africhart-backups
AWS_ENDPOINT=https://s3.eu-central-003.backblazeb2.com   # omit for AWS
AWS_USE_PATH_STYLE_ENDPOINT=true                          # usually true for non-AWS

BACKUP_OFFSITE_DISK=s3
BACKUP_NOTIFICATION_EMAIL=owner@theclinic.com
```

Leave `BACKUP_OFFSITE_DISK` blank to keep backups local-only (not recommended for
production — "your records, safe forever" needs off-site).

**Manual run / verify:**

```
php artisan backup:run        # create a backup now
php artisan backup:list       # show stored backups + health
```

`mysqldump` must be on the server `PATH`. If it lives elsewhere, set the dump
binary path under the connection's `dump` key in `config/database.php`.

---

## 4. Tested restore (do this — a backup you've never restored doesn't count)

Rehearse at least once per deployment, into a **scratch** database (never the live one):

```bash
# 1. Fetch the latest archive (from the backup disk) and unzip it.
#    The archive contains db-dumps/<connection>.sql and the storage/app files.
unzip -o <downloaded-backup>.zip -d /tmp/restore-test

# 2. Create a throwaway database and load the dump into it.
mysql -u root -p -e "CREATE DATABASE africhart_restore_test;"
mysql -u root -p africhart_restore_test < /tmp/restore-test/db-dumps/mysql-africhart_emr.sql

# 3. Sanity-check row counts against production expectations.
mysql -u root -p africhart_restore_test -e \
  "SELECT (SELECT COUNT(*) FROM patients) AS patients,
          (SELECT COUNT(*) FROM consultations) AS consultations,
          (SELECT COUNT(*) FROM invoices) AS invoices;"

# 4. Drop the scratch DB when satisfied.
mysql -u root -p -e "DROP DATABASE africhart_restore_test;"
```

Record the date of the last successful restore drill here:

- Last verified restore: _<fill in>_
