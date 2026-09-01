<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InviteAcceptanceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientQueueController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\Settings\ClinicProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffInvitationController;
use App\Http\Middleware\InitializeTenancyBySubdomain;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant routes — the clinic application
|--------------------------------------------------------------------------
|
| Served on a clinic's own subdomain: <clinic>.africhartemr.com. Every route
| here runs INSIDE tenant context, against that clinic's own database.
|
| The middleware order matters and is not arbitrary:
|
|   web                            session, cookies, CSRF
|   InitializeTenancyBySubdomain   resolves clinics.subdomain and swaps the
|                                  default connection to that clinic's database
|   PreventAccessFromCentralDomains  refuses these routes on a central domain
|
| Identification runs BEFORE the auth middleware inside the groups below, so
| `auth` resolves against the tenant's `staff` table rather than looking for
| one in the central database — where none exists.
|
*/

/*
 * The domain constraint is NOT decoration — without it these routes COLLIDE
 * with the central ones.
 *
 * Laravel's RouteCollection keys its lookup by method+domain+uri, so two routes
 * sharing a method and URI on no domain silently overwrite each other, last
 * registration winning. Tenant routes are registered after web.php (the
 * provider maps them in app->booted), so a tenant `GET /` replaced the
 * marketing home page and the central site started 404ing. Scoping tenant
 * routes to the subdomain pattern makes the two sets disjoint.
 *
 * The {clinic} placeholder is required for the wildcard to work but is never
 * used — the middleware resolves the tenant itself and then forgets the
 * parameter, so controller signatures are untouched.
 */
Route::domain('{clinic}.'.config('tenancy.root_domain'))->middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // A clinic's front door is its dashboard, not a marketing page.
    Route::redirect('/', '/dashboard')->name('tenant.home');

    // --- Guests only ---
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

        /*
         * Invitation acceptance — what replaced /register.
         *
         * There is no self-registration any more. /register let a visitor choose
         * their own role and then supply one of four codes that were identical on
         * every clinic in the system, so a leaked admin code was an admin account
         * at EVERY clinic. It is deleted rather than disabled: a hole reachable by
         * setting a config value is a hole someone can reopen by accident.
         *
         * Throttled to bound abuse of the endpoint, NOT to stop guessing — 64
         * random alphanumeric characters are far past brute force, and no
         * per-minute limit is what makes that true.
         *
         * 30/min rather than something tighter because the limiter keys on IP
         * and a clinic is one IP: on onboarding day several staff accept
         * invitations from the same office connection, each spending a GET, a
         * reload or two and a POST. At 10/min the fourth person to click their
         * link gets a 429 in the middle of setting a password, which looks like
         * a broken invitation.
         *
         * No {token} pattern constraint on purpose: a malformed token would then be
         * refused by the router with Laravel's own 404 page, while an unknown but
         * well-formed one reaches our styled page. That visible difference is
         * exactly the signal the identical-failure rule exists to remove, so both
         * go through the controller.
         */
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/invite/{token}', [InviteAcceptanceController::class, 'show'])->name('invite.show');
            Route::post('/invite/{token}', [InviteAcceptanceController::class, 'accept'])->name('invite.accept');
        });

        Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
    });

    // --- Authenticated (email not necessarily verified yet) ---
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Email verification (code-based)
        Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
            ->middleware('throttle:6,1')->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')->name('verification.send');
    });

    // --- Authenticated AND verified ---
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Patient management — all clinical staff and reception
        Route::middleware('role:admin,doctor,nurse,receptionist')->group(function () {
            Route::resource('patients', PatientController::class)->except(['destroy']);
        });

        // Archiving (soft-delete) and restoring patients — admin only.
        Route::middleware('role:admin')->group(function () {
            Route::delete('/patients/{patient}/archive', [PatientController::class, 'archive'])->name('patients.archive');
            Route::patch('/patients/{patient}/restore', [PatientController::class, 'restore'])->withTrashed()->name('patients.restore');
        });

        // --- Patient Queue ---
        // Viewing the queue is open to everyone; mutating it is role-gated.
        Route::get('/queue', [PatientQueueController::class, 'index'])->name('queue.index');
        Route::get('/queue/live', [PatientQueueController::class, 'live'])->name('queue.live');

        Route::middleware('role:admin,nurse,receptionist')->group(function () {
            Route::post('/queue', [PatientQueueController::class, 'store'])->name('queue.store');
            Route::patch('/queue/{queue}/assign', [PatientQueueController::class, 'assign'])->name('queue.assign');
            Route::patch('/queue/{queue}/vitals', [PatientQueueController::class, 'recordVitals'])->name('queue.vitals');
        });

        Route::middleware('role:admin,receptionist')->group(function () {
            Route::patch('/queue/{queue}/cancel', [PatientQueueController::class, 'cancel'])->name('queue.cancel');
        });

        // --- Consultations ---
        // Viewing + recording vitals: admin, doctor, nurse.
        Route::middleware('role:admin,doctor,nurse')->group(function () {
            Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
            Route::get('/consultations/live', [ConsultationController::class, 'liveIndex'])->name('consultations.live');
            Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])
                ->whereNumber('consultation')->name('consultations.show');
            Route::get('/consultations/{consultation}/live', [ConsultationController::class, 'liveShow'])
                ->whereNumber('consultation')->name('consultations.live.show');
            Route::patch('/consultations/{consultation}/vitals', [ConsultationController::class, 'recordVitals'])
                ->whereNumber('consultation')->name('consultations.vitals');
        });

        // Creating / editing / completing + prescriptions: admin, doctor.
        Route::middleware('role:admin,doctor')->group(function () {
            Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
            Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
            Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])
                ->whereNumber('consultation')->name('consultations.edit');
            Route::put('/consultations/{consultation}', [ConsultationController::class, 'update'])
                ->whereNumber('consultation')->name('consultations.update');
            Route::patch('/consultations/{consultation}/complete', [ConsultationController::class, 'complete'])
                ->whereNumber('consultation')->name('consultations.complete');

            // Prescriptions are nested under a consultation.
            Route::post('/consultations/{consultation}/prescriptions', [PrescriptionController::class, 'store'])
                ->whereNumber('consultation')->name('prescriptions.store');
            Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])
                ->name('prescriptions.destroy');
        });

        // --- Invoices ---
        // Viewing: admin, receptionist, doctor.
        Route::middleware('role:admin,doctor,receptionist')->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        });

        // Managing: admin, receptionist.
        Route::middleware('role:admin,receptionist')->group(function () {
            Route::get('/billing/ready-to-invoice/live', [DashboardController::class, 'readyToInvoiceLive'])->name('billing.ready.live');
            Route::post('/invoices/from-consultation/{consultation}', [InvoiceController::class, 'generateFromConsultation'])
                ->whereNumber('consultation')->name('invoices.generate');
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::post('/invoices/{invoice}/items', [InvoiceController::class, 'addItem'])->name('invoices.items.store');
            Route::patch('/invoice-items/{item}', [InvoiceController::class, 'updateItem'])->name('invoices.items.update');
            Route::delete('/invoice-items/{item}', [InvoiceController::class, 'removeItem'])->name('invoices.items.destroy');
            Route::patch('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
            Route::patch('/invoices/{invoice}/pay', [InvoiceController::class, 'markPaid'])->name('invoices.pay');
        });

        // --- Staff management (admin only) ---
        Route::middleware('role:admin')->group(function () {
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::delete('/staff/{user}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');
            Route::patch('/staff/{user}/reactivate', [StaffController::class, 'reactivate'])->withTrashed()->name('staff.reactivate');

            // Invitations are how staff join a clinic. Admin-only by virtue of
            // this group — the only way to create an account here.
            Route::post('/staff/invitations', [StaffInvitationController::class, 'store'])->name('staff.invitations.store');
            Route::delete('/staff/invitations/{invitation}', [StaffInvitationController::class, 'destroy'])->name('staff.invitations.destroy');
        });

        /*
         * --- Settings hub (admin only) ---
         *
         * /settings redirects rather than rendering its own landing page: an
         * index that only lists the sections already in the left-hand nav is a
         * page whose entire content is its own navigation.
         */
        Route::redirect('/settings', '/settings/profile')->name('settings.index');
        Route::get('/settings/profile', [ClinicProfileController::class, 'edit'])->name('settings.profile.edit');
        Route::put('/settings/profile', [ClinicProfileController::class, 'update'])->name('settings.profile.update');

        // --- Drug catalog (admin only) ---
        Route::middleware('role:admin')->group(function () {
            Route::get('/drug-catalog', [MedicationController::class, 'index'])->name('medications.index');
            Route::post('/drug-catalog', [MedicationController::class, 'store'])->name('medications.store');
            Route::put('/drug-catalog/{medication}', [MedicationController::class, 'update'])->name('medications.update');
            Route::patch('/drug-catalog/{medication}/toggle', [MedicationController::class, 'toggle'])->name('medications.toggle');
        });

        // --- Audit log (admin only) ---
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');

        // --- Data export (admin only) ---
        Route::get('/export/patients', [ExportController::class, 'patients'])->name('export.patients');
        Route::get('/export/consultations', [ExportController::class, 'consultations'])->name('export.consultations');
        Route::get('/export/invoices', [ExportController::class, 'invoices'])->name('export.invoices');
    });

});
