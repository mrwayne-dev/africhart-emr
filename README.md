# AfriChart EMR — Phase 0

Electronic Medical Records system for private clinics in Nigeria.

## Tech Stack
- Laravel 13 (PHP 8.3)
- Blade + Tailwind CSS v4
- Alpine.js (modals, toasts, password toggle)
- MySQL 8.0
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

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `tempnam(): file created in the system's temporary directory` (500) | Apache can't write compiled views to `storage/` (perms reset by `wayne serve`) | `chmod -R 777 storage bootstrap/cache` (or the `chown www-data` + `775` version), then `php artisan view:clear` |
| Verification / reset emails not arriving | SMTP not configured, wrong port encryption, or rejected sender | Set `MAIL_ENCRYPTION=ssl` for port 465; `MAIL_FROM_ADDRESS` must be owned by the SMTP account; check `storage/logs/laravel.log` |
| `npm run build` → `vite: Permission denied` | exec bit missing on the vite binary | `node node_modules/vite/bin/vite.js build` |
| 419 Page Expired on a form | Missing/expired CSRF token | Ensure the form has `@csrf`; refresh the page |

## Demo Credentials

Seeded accounts (pre-verified, so they skip email verification):

| Role   | Email                | Password |
|--------|----------------------|----------|
| Admin  | admin@africhart.com  | password |
| Doctor | doctor@africhart.com | password |

New accounts can also self-register at `/register`, but only with the matching
**invite code** for the chosen role (set in `.env` as `REGISTER_CODE_ADMIN` /
`REGISTER_CODE_DOCTOR`). After registering, the user verifies their email with a
6-digit code sent to their inbox.

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
