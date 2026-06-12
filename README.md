# AfriChart EMR — Phase 1 (Full MVP)

Electronic Medical Records system for private clinics in Nigeria. Phase 1 adds the
full clinical workflow (queue → consultation → prescriptions → invoice), four staff
roles, a REST API with interactive docs, and a set of overdelivery features
(patient timeline, audit trail, PDF invoices, dashboard charts, CSV export, print views).

## Tech Stack
- Laravel 13 (PHP 8.3)
- Blade + Tailwind CSS v4
- Alpine.js (modals, toasts, dynamic prescription form, password toggle)
- Chart.js (dashboard analytics)
- MySQL 8.0
- Laravel Sanctum (API tokens) + l5-swagger (OpenAPI docs)
- barryvdh/laravel-dompdf (PDF invoices)
- Phosphor Icons (Blade)

## Architecture
Repository + Service layer pattern:
```
Route → Controller → Service (business logic) → Repository (DB queries) → Model
```
- **Controllers** handle HTTP (thin — receive request, return response)
- **Services** handle business logic (patient ID generation, dashboard stats)
- **Repositories** handle data access (queries, filters, pagination)
- **Models** handle Eloquent relationships and casts

## Setup
1. Clone the repository
2. `composer install`
3. `npm install && npm run build`
4. Copy `.env.example` to `.env` and set:
   - database credentials (`DB_DATABASE=africhart_emr`, `DB_USERNAME`, `DB_PASSWORD`)
   - mail (SMTP) credentials for verification / reset emails — note `MAIL_ENCRYPTION=ssl`
     for port 465, and `MAIL_FROM_ADDRESS` must be an address your SMTP account owns
   - registration invite codes (`REGISTER_CODE_ADMIN`, `REGISTER_CODE_DOCTOR`)
5. `php artisan key:generate`
6. Create the database: `mysql -u root -p -e "CREATE DATABASE africhart_emr;"`
7. `php artisan migrate --seed`
8. Serve the app (see below)

## Running the app

### Option A — `php artisan serve`
```bash
php artisan serve     # http://localhost:8000
```

### Option B — Apache via the `wayne` CLI (`.test` domain)
A root `.htaccess` rewrites requests into `public/`, so Laravel runs under the
project-root DocumentRoot that `wayne` configures:
```bash
wayne serve africhart-emr        # https://africhart-emr.test (HTTPS via mkcert)
# give Apache (www-data) write access to runtime dirs:
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
wayne open africhart-emr
```

> ⚠️ **`wayne serve` re-chowns the whole project to your user each run**, which makes
> `storage/` unwritable by Apache (`www-data`) and causes a `tempnam(): file created in
> the system's temporary directory` 500 on the next page load. **Re-run the two perms
> commands above after every `wayne serve`.** No-sudo alternative for local dev:
> `chmod -R 777 storage bootstrap/cache`.

## Database dumps

Ready-to-import SQL lives in `database/schema/`:

| File | Contents |
|---|---|
| `africhart_emr.sql` | Full dump — structure **+ demo data** (4 users, 25 patients, 12 consultations + prescriptions + invoices, a live queue, audit log, migration rows). Import this for an instant working demo. |
| `africhart_emr-structure.sql` | Structure only (empty tables). |

Import (no `CREATE DATABASE` in the file, so pick the target DB yourself):
```bash
mysql -u <user> -p <database> < database/schema/africhart_emr.sql
```
Regenerate after schema changes: `mysqldump -u root -p --no-tablespaces --add-drop-table africhart_emr > database/schema/africhart_emr.sql`.

## Deployment (live server — shared hosting / cPanel)

The live demo runs on cPanel shared hosting at `https://africhart.mgbah.dev`.

**1. PHP 8.3.** Composer/Laravel require PHP ≥ 8.3. On cPanel set the domain's version in
**MultiPHP Manager**, and for CLI use the 8.3 binary (find it with `ls -d /opt/cpanel/ea-php*`):
```bash
alias php=/opt/cpanel/ea-php83/root/usr/bin/php
```
Enable these extensions in **Select PHP Version**: `bcmath, ctype, curl, dom, fileinfo,
intl, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, zip`.

**2. Get the code + dependencies.** Two options:
- **Composer/npm on the server (preferred):** `composer install --no-dev --optimize-autoloader`,
  then `npm install && npm run build` (or upload `public/build` if there's no Node).
- **Ship artifacts via git (used for this deploy, when the host lacks Composer/npm):**
  `vendor/` + `public/build/` were force-committed, pulled on the server, then history was
  squashed back to one clean commit. ⚠️ Because the clean history no longer tracks those
  folders, **do not run `git pull` / `git reset --hard` on the server** — it would delete
  them. For future updates, re-ship them the same way or switch to the Composer/npm option.

**3. Configure `.env`** (production):
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://africhart.mgbah.dev
```
Set DB + mail + invite-code values. **No trailing spaces** in values, and quote any with
special characters (e.g. `DB_PASSWORD="..."`). Then `php artisan key:generate` if `APP_KEY`
is blank.

**4. Database.** Import `database/schema/africhart_emr.sql` via phpMyAdmin/CLI, **or**
`php artisan migrate --seed`.

**5. Permissions & web root.**
```bash
chmod -R 775 storage bootstrap/cache
```
Point the subdomain's document root at `…/africhart.mgbah.dev/public`, or rely on the
committed root `.htaccess` (it rewrites requests into `public/`).

**6. (Optional) production caches:** `php artisan config:cache route:cache view:cache`.

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `tempnam(): file created in the system's temporary directory` (500) | Apache can't write compiled views to `storage/` (perms reset by `wayne serve`) | `chmod -R 777 storage bootstrap/cache` (or the `chown www-data` + `775` version), then `php artisan view:clear` |
| Verification / reset emails not arriving | SMTP not configured, wrong port encryption, or rejected sender | Set `MAIL_ENCRYPTION=ssl` for port 465; `MAIL_FROM_ADDRESS` must be owned by the SMTP account; check `storage/logs/laravel.log` |
| `npm run build` → `vite: Permission denied` | exec bit missing on the vite binary | `node node_modules/vite/bin/vite.js build` |
| 419 Page Expired on a form | Missing/expired CSRF token | Ensure the form has `@csrf`; refresh the page |

## Demo Credentials

Seeded accounts (pre-verified, so they skip email verification):

| Role         | Email                  | Password |
|--------------|------------------------|----------|
| Admin        | admin@africhart.com    | password |
| Doctor       | doctor@africhart.com   | password |
| Nurse        | nurse@africhart.com    | password |
| Receptionist | reception@africhart.com| password |

New accounts can also self-register at `/register`, but only with the matching
**invite code** for the chosen role (set in `.env` as `REGISTER_CODE_ADMIN` /
`REGISTER_CODE_DOCTOR` / `REGISTER_CODE_NURSE` / `REGISTER_CODE_RECEPTIONIST`).
After registering, the user verifies their email with a 6-digit code sent to their inbox.

## REST API

All modules are exposed under `/api/v1` (Sanctum bearer-token auth). Get a token with
`POST /api/v1/auth/login`, then send `Authorization: Bearer <token>`. Interactive
OpenAPI docs are at **`/api/documentation`** (regenerate with `php artisan l5-swagger:generate`).

```bash
# Example: log in and list patients
curl -X POST https://africhart-emr.test/api/v1/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"doctor@africhart.com","password":"password"}'

curl https://africhart-emr.test/api/v1/patients \
  -H 'Accept: application/json' -H 'Authorization: Bearer <token>'
```

## Features
**Phase 0**
- Login with role-based access (Admin / Doctor)
- Separate dashboards per role (Admin sees clinic stats; Doctor sees recent patients)
- Patient registration with validation and auto-generated patient IDs (`ACH-YYYYMMDD-XXXX`)
- Patient listing with search (name / phone / patient ID) and blood-group filter
- Patient profile viewing and editing
- Seeded with 25 realistic patient records

**Phase 0.5 — auth & interactive UX**
- Self-service registration gated by per-role invite codes (Doctor / Admin tabs)
- Email verification via a 6-digit code (10-minute expiry, resendable)
- Forgot / reset password (emailed reset link)
- Show/hide password toggle on all auth forms
- Toast notifications for every action; confirmation modal on logout
- Patient register/edit in modal dialogs (inline validation), with full-page fallbacks

**Phase 0.6 — feedback, alerts & responsiveness**
- Prominent toast notifications (shadow + accent border), including on login and logout
- Admin email alerts: all admins are emailed on new patient, patient update, new staff
  registration, and email verification (the acting user is excluded)
- Loading states (spinner + disabled button) on every form submit and the patient modal
- Fully responsive: off-canvas sidebar drawer with a hamburger on mobile, static on desktop

**Phase 1 — full MVP**
- Four staff roles (Admin / Doctor / Nurse / Receptionist) with role-specific dashboards
  and Laravel Policies enforcing fine-grained access (e.g. only the owning doctor or an
  admin can edit a consultation; nurses record vitals but can't prescribe)
- **Patient queue** — receptionists/nurses check patients in (daily auto-numbered),
  assign a doctor, and the status flows waiting → in consultation → completed
- **Consultations** — clinical workspace with chief complaint, notes, diagnosis, plan,
  vitals (auto-BMI) and a "complete" action
- **Prescriptions** — dynamic multi-row form with common-medication autocomplete
  (`storage/app/data/medications.json`), nested under consultations
- **Invoices** — generate from a consultation (auto-adds consultation fee + medication
  lines), inline price/quantity editing with live totals, issue, mark paid, **PDF download**
- **Patient timeline** — chronological feed of registration, consultations and invoices
- **Audit trail** — every create/update/delete on clinical data is logged; admins get a
  searchable/filterable log and a live activity feed
- **Dashboard analytics** — Chart.js registrations (line), revenue (bar) and consultation
  status (donut) charts on the admin dashboard
- **REST API** under `/api/v1` (Sanctum) with interactive Swagger docs at `/api/documentation`
- **CSV export** (admin) for patients, consultations and invoices; **print-friendly** views
  for patient records, consultations and invoices
- Demo data: `Phase1DemoSeeder` seeds consultations, prescriptions, invoices and a live queue
