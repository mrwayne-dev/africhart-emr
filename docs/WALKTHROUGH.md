# AfriChart EMR — Full Project Walkthrough

> A top-to-bottom explanation of how this app is built, written so you can understand
> every piece as if you wrote it yourself. No prior Laravel assumed — concepts are
> explained the first time they appear.

## Table of Contents

1. [The mental model: how a Laravel request works](#1-the-mental-model)
2. [The architecture: why we split code into layers](#2-the-architecture)
3. [Project setup & key decisions](#3-project-setup--key-decisions)
4. [Configuration: `.env` and config](#4-configuration)
5. [Enums: type-safe fixed choices](#5-enums)
6. [Migrations: defining the database](#6-migrations)
7. [Models: talking to the database](#7-models)
8. [Middleware: gatekeeping requests](#8-middleware)
9. [Repositories: where queries live](#9-repositories)
10. [Services: where business logic lives](#10-services)
11. [Form Requests: validating input](#11-form-requests)
12. [Controllers: the thin coordinators](#12-controllers)
13. [Routes: the URL map](#13-routes)
14. [Authentication: how login actually works](#14-authentication)
15. [Views & Blade: the HTML layer](#15-views--blade)
16. [Design system: Tailwind v4 + tokens](#16-design-system)
17. [Seeders: demo data](#17-seeders)
18. [End-to-end trace: registering a patient](#18-end-to-end-trace)
19. [How to run, test, and extend](#19-how-to-run-test-and-extend)
20. [Glossary](#20-glossary)

**Part 2 — Auth flows & interactive UX**
21. [Registration with invite codes](#21-registration-with-invite-codes)
22. [Email verification by code](#22-email-verification-by-code)
23. [Password reset](#23-password-reset)
24. [Alpine.js: the interactive layer](#24-alpinejs-the-interactive-layer)
25. [Patient forms in modals (fetch + JSON)](#25-patient-forms-in-modals-fetch--json)

**Part 3 — Feedback, alerts & responsiveness**
26. [Toasts everywhere (incl. login/logout)](#26-toasts-everywhere-incl-loginlogout)
27. [Email alerts to admins](#27-email-alerts-to-admins)
28. [Loading states](#28-loading-states)
29. [Responsive layout](#29-responsive-layout)
30. [Running under Apache (the storage permissions gotcha)](#30-running-under-apache-the-storage-permissions-gotcha)

**Part 4 — Shipping it**
31. [Migrations vs a SQL dump](#31-migrations-vs-a-sql-dump)
32. [Deploying to shared hosting (cPanel)](#32-deploying-to-shared-hosting-cpanel)

---

## 1. The mental model

Laravel is a **server-rendered web framework**. The whole app is a loop:

```
Browser sends an HTTP request  ──►  Laravel runs your PHP  ──►  Laravel sends back HTML
        ▲                                                                  │
        └──────────────────────  user clicks / submits  ◄─────────────────┘
```

Every page view or form submission is one trip through this loop. There is no
JavaScript framework here — when you click "Register Patient", the browser does a full
POST to the server, the server saves the row and responds with a redirect, and the
browser loads the next page. Simple and reliable.

**The request lifecycle** (what Laravel does on every request):

1. The request hits `public/index.php` — the single entry point ("front controller").
2. `bootstrap/app.php` builds the application and registers middleware.
3. Laravel matches the URL against `routes/web.php` to find which **controller method**
   should handle it.
4. **Middleware** runs first (e.g. "is this user logged in? do they have the right
   role?"). Middleware can let the request through or stop it (redirect / 403).
5. The **controller method** runs. It coordinates the work and returns a response.
6. The response (usually a rendered **Blade view**, i.e. HTML) goes back to the browser.

Keep this loop in your head. Every file in this project is one station along it.

---

## 2. The architecture

A naive Laravel app puts *everything* in the controller: validation, database queries,
business rules, all mixed together. That becomes unmaintainable. This project uses a
layered pattern (it was already scaffolded in the repo, and we kept it consistent):

```
Route ──► Controller ──► Service ──► Repository ──► Model ──► Database
                          (logic)     (queries)   (table row)
```

Each layer has **one job**:

| Layer | Job | Example in this app |
|---|---|---|
| **Controller** | Handle HTTP. Receive the request, call a service, return a response. Stays *thin*. | `PatientController::store()` |
| **Service** | Business logic / rules. | `PatientService::generatePatientId()` builds `ACH-20260607-0001` |
| **Repository** | Database access. All the Eloquent queries. | `PatientRepository::getPaginated()` builds the search query |
| **Model** | Represents one table; defines relationships & casts. | `Patient` maps to the `patients` table |

**Why bother?** Three reasons the client explicitly cares about:
- **Testability** — you can test `PatientService` without HTTP.
- **Reuse** — the seeder and the controller both call `PatientService::createPatient()`,
  so patient-ID logic exists in exactly one place.
- **Clarity** — when something breaks, you know which layer to open.

Rule of thumb: *Controllers don't write queries. Services don't know about HTTP.
Repositories don't make decisions.*

---

## 3. Project setup & key decisions

The repo came pre-scaffolded (base classes, Tailwind v4, MySQL config). We built Phase 0
on top. A few decisions are worth understanding because they differ from the original
build guide (the PDF assumed an older Laravel):

- **Laravel 13 + PHP 8.3.** Modern Laravel has no `app/Http/Kernel.php`; middleware is
  registered in `bootstrap/app.php` instead. Good to know if you read older tutorials.
- **Tailwind CSS v4 (not v3).** v4 is configured *in CSS* (`@import "tailwindcss"` and an
  `@theme {}` block) — there is **no `tailwind.config.js`**. This is a big change from
  most tutorials you'll find online.
- **No Laravel Breeze.** Breeze is a scaffolding package for auth pages, but it installs
  Tailwind v3 and fights our v4 setup. Phase 0 only needs login + logout (accounts are
  seeded, no public sign-up), so we hand-wrote auth in a few small files — cleaner and
  fully under our control.
- **MySQL** instead of the default SQLite, for production realism.
- **Served via the `wayne` CLI** over `https://africhart-emr.test` (see §19).

---

## 4. Configuration

Laravel reads environment-specific settings from the **`.env`** file (never committed —
it holds secrets). Code reads these via `config()` and `env()`.

Relevant lines in `.env`:

```ini
APP_NAME="africhart-emr"
APP_URL=https://africhart-emr.test   # used when Laravel generates absolute URLs/assets

DB_CONNECTION=mysql                   # use the MySQL driver
DB_DATABASE=africhart_emr
DB_USERNAME=root
DB_PASSWORD="#mrwayne10ISKING"        # quoted — a bare # would start a comment

SESSION_DRIVER=file                   # where login sessions are stored
```

`config/database.php` defines *how* to connect for each driver; `.env` chooses *which*
driver and supplies the credentials. The pattern throughout Laravel: **config files hold
structure, `.env` holds the values that change per machine.**

---

## 5. Enums

**File:** `app/Enums/UserRole.php`, `app/Enums/BloodGroup.php`

An **enum** ("enumeration") is a type with a fixed set of allowed values. Instead of
passing the string `'admin'` around (easy to typo), we use `UserRole::Admin`.

```php
enum UserRole: string
{
    case Admin = 'admin';     // UserRole::Admin->value === 'admin'
    case Doctor = 'doctor';

    public function label(): string          // a method on the enum
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Doctor => 'Doctor',
        };
    }
}
```

`: string` means each case is *backed* by a string — that string is what gets stored in
the database. `label()` is a helper so views can show a friendly name. `BloodGroup` works
the same way with 8 cases (`A+`, `A-`, … `O-`); its `label()` just returns the value.

**Why this matters:** because the model *casts* these columns to enums (next sections),
`$user->role` is a `UserRole` object, not a raw string — so `$user->role === UserRole::Admin`
is type-safe, and `$patient->blood_group->label()` works in Blade.

---

## 6. Migrations

**Files:** `database/migrations/*.php`

A **migration** is a versioned PHP script that builds or changes a database table. Run
`php artisan migrate` and Laravel executes any migrations it hasn't run yet (it tracks
them in a `migrations` table). This means the schema lives in code, in git — anyone can
recreate the exact database with one command.

### Adding `role` to users

`...add_role_to_users_table.php`:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('doctor')->after('email');
    });
}
public function down(): void   // how to undo this migration
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}
```

`up()` applies the change, `down()` reverses it (used by `migrate:rollback`).
`Schema::table` *modifies* an existing table; `Schema::create` makes a new one.

### The patients table

`...create_patients_table.php`:

```php
Schema::create('patients', function (Blueprint $table) {
    $table->id();                                   // auto-increment primary key `id`

    $table->string('full_name');
    $table->date('date_of_birth');
    $table->string('phone', 20);

    $table->string('blood_group', 5);               // 'A+', 'O-', ...
    $table->text('allergies')->nullable();          // optional, can be NULL

    $table->string('patient_id', 20)->unique();     // ACH-YYYYMMDD-XXXX, no duplicates
    $table->foreignId('registered_by')              // which user created this patient
        ->constrained('users')                      // FK → users.id
        ->onDelete('restrict');                     // block deleting a user who has patients

    $table->timestamps();                           // created_at + updated_at, auto-managed

    $table->index('full_name');                     // speeds up searching/sorting
    $table->index('phone');
    $table->index('patient_id');
});
```

Key ideas:
- **`patient_id` vs `id`:** `id` is the internal database key (1, 2, 3…). `patient_id`
  is the *human* identifier shown to staff (`ACH-20260607-0001`). We expose `id` in URLs
  but show `patient_id` to users.
- **Foreign key (`registered_by`)** links a patient to the user who registered them.
  `onDelete('restrict')` is a safety rule: the database refuses to delete a user who
  still has patients — you never silently orphan medical records.
- **Indexes** are like a book's index: they let MySQL find rows by `full_name`/`phone`/
  `patient_id` quickly instead of scanning every row.

---

## 7. Models

**Files:** `app/Models/User.php`, `app/Models/Patient.php`

A **model** is a PHP class representing one table. Laravel's ORM, **Eloquent**, lets you
work with rows as objects: `Patient::find(1)`, `$patient->full_name`, `$patient->save()`.
One model instance = one row.

### Patient model

```php
#[Fillable([
    'full_name', 'date_of_birth', 'phone',
    'blood_group', 'allergies', 'patient_id', 'registered_by',
])]
class Patient extends Model
{
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',              // returns a Carbon date object
            'blood_group'   => BloodGroup::class,   // returns a BloodGroup enum
        ];
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }
}
```

Three concepts here:

**1. `#[Fillable([...])]` — mass-assignment protection.**
When you do `Patient::create($data)` with an array of form input, Eloquent only saves the
columns listed as fillable. This blocks a malicious user from injecting a field you didn't
intend (e.g. forcing `registered_by` to someone else). *(This repo uses the modern PHP
**attribute** syntax `#[Fillable(...)]`; older tutorials use a `protected $fillable = []`
property — same effect.)*

**2. Casts — converting raw DB values into rich types.**
The DB stores `date_of_birth` as a plain date string and `blood_group` as `'O+'`. The
casts turn them into a Carbon date object and a `BloodGroup` enum automatically when you
read them. That's why `$patient->date_of_birth->age` and `$patient->blood_group->label()`
work.

**3. Relationships — `registeredBy()`.**
`belongsTo(User::class, 'registered_by')` says "each patient belongs to one user, via the
`registered_by` column". Now `$patient->registeredBy->name` gives the registering user's
name. The reverse side lives on the User model:

```php
public function registeredPatients(): HasMany
{
    return $this->hasMany(Patient::class, 'registered_by');
}
```

So `$user->registeredPatients` is a collection of that user's patients. `belongsTo` /
`hasMany` are two ends of the same one-to-many link.

**Accessor — `getAgeAttribute()`.** A "virtual" property. There's no `age` column, but the
naming convention `get{Name}Attribute` lets you read `$patient->age` and it computes from
`date_of_birth`. Keeps derived data out of the database.

### User model

`User` is the same idea plus authentication. It extends `Authenticatable` (gives Laravel
login powers) and adds:

```php
protected function casts(): array
{
    return [
        'password' => 'hashed',     // auto-hashes when you set a password
        'role'     => UserRole::class,
    ];
}
public function isAdmin(): bool  { return $this->role === UserRole::Admin; }
public function isDoctor(): bool { return $this->role === UserRole::Doctor; }
```

The `'password' => 'hashed'` cast is important: when you write
`User::create(['password' => 'password'])`, Eloquent hashes it for you. (That's why the
seeder passes a plain password — do **not** also call `bcrypt()`, or you'd double-hash it
and login would fail.) `isAdmin()`/`isDoctor()` are readability helpers used in the
dashboard controller and middleware.

---

## 8. Middleware

**File:** `app/Http/Middleware/RoleMiddleware.php`

Middleware is code that runs **before** your controller, like a checkpoint. Laravel ships
with an `auth` middleware ("must be logged in"). We wrote a `role` middleware ("must have
one of these roles"):

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! auth()->check()) {
        return redirect()->route('login');      // not logged in → bounce to login
    }

    $userRole = auth()->user()->role->value;     // e.g. 'admin'

    if (! in_array($userRole, $roles, true)) {
        abort(403, 'You do not have permission to access this page.');
    }

    return $next($request);                      // allowed → continue to the controller
}
```

`$next($request)` is "let the request proceed". If we return a redirect or call `abort()`
*instead*, the controller never runs. `string ...$roles` lets us pass parameters from the
route like `role:admin,doctor` — those become `$roles = ['admin', 'doctor']`.

**Why role checks live in middleware, not in views:** a user can't bypass middleware by
typing the URL directly. Hiding a button in the UI is not security; the middleware is.

**Registering it.** Modern Laravel registers middleware aliases in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => RoleMiddleware::class,
    ]);
})
```

This maps the short name `role` (used in routes) to our class.

---

## 9. Repositories

**Files:** `app/Repositories/BaseRepository.php` (existing), `PatientRepository.php`

A **repository** is the only place that talks to the database for a given model. The base
class (already in the repo) provides generic CRUD: `all()`, `find()`, `create()`,
`update()`, `delete()`, `paginate()`. `PatientRepository` extends it and adds
patient-specific queries.

The interesting one is the search query:

```php
public function getPaginated(?string $search = null, ?string $bloodGroup = null, int $perPage = 15): LengthAwarePaginator
{
    $query = $this->model->with('registeredBy');   // start a query, eager-load the relation

    if ($search) {
        $query->where(function ($q) use ($search) {       // group the OR conditions
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('patient_id', 'like', "%{$search}%");
        });
    }

    if ($bloodGroup) {
        $query->where('blood_group', $bloodGroup);
    }

    return $query->latest()->paginate($perPage)->withQueryString();
}
```

What to notice:

- **Query builder is chainable & lazy.** Each `->where(...)` adds a condition; nothing
  hits the database until `->paginate()` runs. So we conditionally add `where` clauses
  only when a search term or filter is present.
- **The grouped closure** `where(function ($q) { ... })` wraps the three `orWhere`s in
  parentheses, producing `WHERE (full_name LIKE ? OR phone LIKE ? OR patient_id LIKE ?)
  AND blood_group = ?`. Without the group, the `AND blood_group` would bind incorrectly.
- **`with('registeredBy')`** is **eager loading** — it fetches all patients *and* their
  registering users in 2 queries instead of 1-per-patient (the "N+1 problem").
- **`->paginate(15)`** returns 15 rows plus page metadata and ready-made page links.
- **`->withQueryString()`** keeps `?search=…&blood_group=…` on the pagination links, so
  page 2 of a filtered search stays filtered. This is what makes the URL shareable.

Other methods (`getRecent`, `countToday`, `countThisWeek`, `count`,
`countByPatientIdPrefix`) are small focused queries used by the dashboard and ID
generator. Notice they all phrase things in Eloquent — **never raw SQL** — which also
protects against SQL injection.

---

## 10. Services

**Files:** `app/Services/BaseService.php` (existing), `PatientService.php`, `DashboardService.php`

Services hold the *decisions and rules*. Controllers call them; they call repositories.

```php
class PatientService extends BaseService
{
    public function __construct(protected PatientRepository $patientRepository)
    {
        parent::__construct($patientRepository);
    }

    public function createPatient(array $data, int $registeredBy): Patient
    {
        $data['patient_id']    = $this->generatePatientId();   // the rule
        $data['registered_by'] = $registeredBy;

        return $this->patientRepository->create($data);        // delegate to the DB layer
    }

    private function generatePatientId(): string
    {
        $today  = now()->format('Ymd');                  // 20260607
        $prefix = "ACH-{$today}-";

        $todayCount = $this->patientRepository->countByPatientIdPrefix($prefix);
        $sequence   = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);  // 1 → "0001"

        return $prefix . $sequence;                      // ACH-20260607-0001
    }
}
```

The **patient ID format** is a business rule, so it lives in the service, not the model or
controller. It counts how many patients already have today's prefix and appends the next
zero-padded sequence number.

**Dependency injection (the `__construct` magic).** Notice we never write
`new PatientRepository(...)`. We just *ask* for it in the constructor and Laravel's
**service container** builds and injects it automatically — including the `Patient` model
the repository itself needs. The promoted `protected PatientRepository $patientRepository`
both declares and stores it in one line. This is why every layer just declares what it
needs and Laravel wires the graph together.

`DashboardService` is the same shape; it exposes `getAdminStats()` (totals/today/this
week) and `getRecentPatients()` by calling the repository's count/recent methods.

---

## 11. Form Requests

**Files:** `app/Http/Requests/StorePatientRequest.php`, `UpdatePatientRequest.php`

A **Form Request** is a class that validates incoming form data *before* the controller
runs. If validation fails, Laravel automatically redirects back to the form with the
error messages and the user's old input — the controller is never even called.

```php
class StorePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }   // role is already checked by middleware

    public function rules(): array
    {
        return [
            'full_name'     => ['required', 'string', 'max:255', 'min:3'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone'         => ['required', 'string', 'max:20', 'regex:/^(\+234|0)[789]\d{9}$/'],
            'blood_group'   => ['required', new Enum(BloodGroup::class)],
            'allergies'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array        // friendlier wording for specific failures
    {
        return [
            'phone.regex' => 'Enter a valid Nigerian phone number (e.g. 08031234567 ...).',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
            'full_name.min' => "Please enter the patient's full name.",
        ];
    }
}
```

- `before_or_equal:today` rejects future birth dates.
- The **regex** enforces Nigerian numbers: `0` or `+234`, then a `7/8/9`, then 9 digits.
- `new Enum(BloodGroup::class)` rejects anything that isn't one of the 8 valid groups —
  the same enum drives validation, the dropdown, and the model cast.

**Important design choice:** these extend Laravel's `FormRequest`, **not** the repo's
`BaseRequest`. `BaseRequest` is built for APIs — on failure it returns a JSON 422 response.
Our patient forms are web pages that need the classic "redirect back and show red error
text under each field" behaviour, which is what plain `FormRequest` does. (We kept
`BaseRequest` untouched for Phase 1's API.)

You "activate" a form request just by type-hinting it in the controller method (next
section) — Laravel sees the type and runs validation before your code.

---

## 12. Controllers

**Files:** `app/Http/Controllers/AuthController.php`, `DashboardController.php`, `PatientController.php`

Controllers are deliberately **thin**: receive request → call a service → return a
response. They extend `BaseController` (which carries the existing `ApiResponse` trait).

### PatientController (the core CRUD)

```php
class PatientController extends BaseController
{
    public function __construct(protected PatientService $patientService) {}

    public function index(Request $request): View
    {
        $patients = $this->patientService->getPatientList(
            search: $request->input('search'),
            bloodGroup: $request->input('blood_group'),
        );
        return view('patients.index', compact('patients'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = $this->patientService->createPatient(
            data: $request->validated(),       // only the validated fields
            registeredBy: auth()->id(),        // the logged-in user's id
        );

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'Patient registered successfully. ID: ' . $patient->patient_id);
    }

    public function show(Patient $patient): View   { ... }
    public function edit(Patient $patient): View   { ... }
    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse { ... }
}
```

Concepts:

- **`store(StorePatientRequest $request)`** — by type-hinting the form request, validation
  runs automatically first. Inside, `$request->validated()` returns *only* the fields that
  passed the rules (safe to pass to the model).
- **Route-model binding** — `show(Patient $patient)`: the route is `/patients/{patient}`.
  Laravel sees the `Patient` type-hint, takes the `{patient}` id from the URL, runs
  `Patient::findOrFail(id)`, and hands you the object (or 404s automatically). You never
  write the lookup.
- **`compact('patients')`** is shorthand for `['patients' => $patients]` — the data passed
  into the view.
- **PRG pattern (Post/Redirect/Get)** — after a successful `store`/`update` we `redirect()`
  instead of returning HTML. This prevents the "resubmit form?" problem on refresh.
  `->with('success', ...)` flashes a one-time message into the session that the layout
  shows on the next page.
- **Named routes** — `route('patients.show', $patient)` builds the URL from a name, so
  URLs aren't hard-coded.

### DashboardController (role branching)

```php
public function index(Request $request): View
{
    $user = $request->user();
    $recentPatients = $this->dashboardService->getRecentPatients();

    if ($user->isAdmin()) {
        $stats = $this->dashboardService->getAdminStats();
        return view('dashboard.admin', compact('stats', 'recentPatients'));
    }
    return view('dashboard.doctor', compact('recentPatients'));
}
```

One URL (`/dashboard`), two views depending on role. Admins get stat cards; doctors get
the recent list only.

### AuthController — covered in §14.

---

## 13. Routes

**File:** `routes/web.php`

Routes map URLs to controller methods. Reading this file tells you the whole surface of
the app.

```php
// Public: send the bare domain to the login page
Route::get('/', fn () => redirect()->route('login'));

// Guests only (already-logged-in users skip these)
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Must be logged in
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Must additionally be admin or doctor
    Route::middleware('role:admin,doctor')->group(function () {
        Route::resource('patients', PatientController::class)->except(['destroy']);
    });
});
```

- **Route groups + middleware** apply a rule to everything inside. The `patients` routes
  are nested inside both `auth` and `role:admin,doctor`, so a request must pass both
  checkpoints.
- **`Route::resource('patients', ...)`** is a shortcut that generates the 7 standard CRUD
  routes (index/create/store/show/edit/update/destroy) and their conventional names. We
  `->except(['destroy'])` because Phase 0 doesn't allow deleting patients.
- **`->name('login')`** gives a route a name so code/views reference it by name, not URL.

Run `php artisan route:list` to see the full generated table — handy for understanding any
Laravel app.

---

## 14. Authentication

**File:** `app/Http/Controllers/AuthController.php`

We hand-built login. It's small because Laravel provides the heavy lifting (`Auth`,
sessions, the `auth` guard).

```php
public function login(Request $request): RedirectResponse
{
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (! Auth::attempt($credentials, $request->boolean('remember'))) {
        return back()
            ->withErrors(['email' => 'These credentials do not match our records.'])
            ->onlyInput('email');
    }

    $request->session()->regenerate();
    return redirect()->intended(route('dashboard'));
}
```

How it works:

- **`Auth::attempt($credentials)`** looks up the user by email, hashes the submitted
  password, and compares it to the stored hash. If it matches, Laravel logs the user in by
  storing their id in the **session** (a server-side store keyed by a cookie in the
  browser). Every later request knows who they are via that cookie.
- **`session()->regenerate()`** issues a fresh session id on login — standard protection
  against "session fixation" attacks.
- **`redirect()->intended(...)`** sends them to the page they originally wanted (if they
  were bounced to login) or the dashboard.
- **Logout** does the reverse: `Auth::logout()`, then invalidate the session and
  regenerate the CSRF token so the old cookie is useless.

**CSRF protection.** Every form includes `@csrf`, which outputs a hidden `_token` field.
Laravel checks that token on POST/PUT to ensure the request came from *your* page, not a
malicious site. Forms without it get a 419 error. This is automatic — just remember the
`@csrf` in every form.

**`auth` vs `guest` middleware:** `auth` blocks logged-out users (→ login). `guest` blocks
logged-in users from seeing the login page again. The `role` middleware adds the
permission layer on top.

---

## 15. Views & Blade

**Files:** `resources/views/**`

**Blade** is Laravel's template engine: HTML with `{{ }}` for output and `@`-directives
for logic. `{{ $patient->full_name }}` prints the value **and escapes it** (prevents XSS —
if a name contained `<script>` it's shown as text, not executed). Use `{{ }}` always
unless you have trusted HTML.

### Layout inheritance

`resources/views/layouts/app.blade.php` is the shared shell (sidebar + topbar + flash +
content area). Pages "extend" it:

```blade
@extends('layouts.app')
@section('title', 'Patients — AfriChart EMR')
@section('content')
    ...page-specific HTML...
@endsection
```

The layout has `@yield('content')` where each page's `@section('content')` gets injected.
This is why the sidebar/topbar are written once and every page reuses them.

The flash message lives in the layout, so any controller that does
`->with('success', ...)` shows a banner automatically:

```blade
@if (session('success'))
    <div class="...">{{ session('success') }}</div>
@endif
```

### Blade components

`resources/views/components/*.blade.php` are reusable UI pieces, used as custom HTML tags
prefixed `x-`:

- `<x-sidebar />`, `<x-topbar />` — the chrome.
- `<x-stat-card title="Total Patients" :value="$stats['total_patients']" icon="phosphor-users" />`
  — a card. Inside `stat-card.blade.php`, `@props([...])` declares its inputs. **A plain
  attribute** (`title="..."`) passes a string; **a colon attribute** (`:value="..."`)
  passes a PHP expression.
- `<x-patient-table :patients="$patients" />` — the reusable table, used on the index page
  *and* both dashboards. Write the table once, use it three places.
- `<x-detail-row label="Phone" :value="$patient->phone" />` — one row of the show page.

`<x-phosphor-users />` etc. come from the Phosphor Icons package — each is an inline SVG
icon, sized with Tailwind classes (`class="w-5 h-5"`).

### The form partial (DRY)

`create.blade.php` and `edit.blade.php` share the exact same fields, so the fields live in
one partial, `patients/form.blade.php`, pulled in with `@include('patients.form')`. It
handles both modes:

```blade
value="{{ old('full_name', $patient?->full_name) }}"
```

`old('full_name', ...)` repopulates the field with what the user typed if validation
failed (so they don't retype everything). The second argument is the fallback — on the
*edit* page it's the existing patient's value; on *create* `$patient` is null (the `?->`
safely returns null), so the field starts empty.

Per-field errors:

```blade
@error('full_name')
    <p class="text-accent">{{ $message }}</p>
@enderror
```

`@error` runs only if that field failed validation — exactly the "red text under the
offending field" behaviour the form requests enable.

The edit form also adds `@method('PUT')`. HTML forms can only send GET/POST, so Laravel
uses a hidden `_method` field to "spoof" PUT/PATCH/DELETE, matching the resource route.

---

## 16. Design system

**Files:** `resources/css/app.css`, `vite.config.js`

The visual language is adapted from the Crestmark spec: a clean light theme with a
warm-off-white + near-black palette, the **General Sans** font, 8px rounded corners,
dark "pill" buttons, and **no shadows** (depth comes from background contrast).

### Tailwind v4 (CSS-configured)

Tailwind is a utility-class CSS framework: you style by composing classes
(`class="px-4 py-2 rounded-full bg-ink text-white"`) instead of writing CSS files. **v4 is
configured in CSS**, not a JS config:

```css
@import url('https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600&display=swap');
@import 'tailwindcss';

@theme {
    --font-sans: 'General Sans', ui-sans-serif, system-ui, sans-serif;

    --color-page: #ffffff;      /* generates bg-page, text-page, border-page */
    --color-warm: #f8f7f5;      /* bg-warm  (cards' warm surface, table zebra) */
    --color-ink:  #1a1a1a;      /* text-ink, bg-ink (dark buttons), border-ink */
    --color-muted:#636363;      /* text-muted (secondary text) */
    --color-line: #ececec;      /* border-line (hairline borders) */
    --color-accent:#c2001d;     /* text-accent (error red) */
    --radius-card: 8px;         /* rounded-card */
}
```

Each token in `@theme` *generates utility classes*. Defining `--color-ink` means you can
write `bg-ink`, `text-ink`, `border-ink` anywhere. That's how the whole app stays
on-palette: a dark pill button is just `bg-ink text-white rounded-full`.

### Vite

`vite.config.js` is the asset build tool. `@vite([...])` in the layouts loads the compiled
CSS/JS. During development `npm run dev` recompiles on save; for the served `.test` site we
run `npm run build`, which writes optimised files to `public/build/` (referenced via a
`manifest.json`). We removed an old Bunny-fonts entry since General Sans now loads from
Fontshare via the CSS `@import`.

---

## 17. Seeders

**Files:** `database/seeders/*.php`

Seeders insert demo/initial data. `php artisan migrate:fresh --seed` drops everything,
re-runs all migrations, and runs `DatabaseSeeder`, which calls the others:

```php
public function run(): void
{
    $this->call([UserSeeder::class, PatientSeeder::class]);
}
```

- `UserSeeder` creates the admin and doctor accounts (`password` is auto-hashed by the
  model cast — no manual `bcrypt()`).
- `PatientSeeder` creates 25 patients with realistic Nigerian names. Crucially it goes
  **through the service**:

  ```php
  $patientService = app(PatientService::class);
  foreach ($patients as $data) {
      $patientService->createPatient($data, $admin->id);
  }
  ```

  Using `createPatient()` (not raw `Patient::create`) means the seeded patients get proper
  `ACH-…` IDs from the same logic the app uses. This is the payoff of the service layer:
  one source of truth for "how a patient is created", reused by both the web form and the
  seeder.

---

## 18. End-to-end trace

Let's follow **one full action — registering a patient** — through every file. This is the
best way to cement how the layers connect.

1. **User clicks "Register Patient."** Browser requests `GET /patients/create`.
2. **Routing** (`routes/web.php`) matches it to `PatientController@create`, but first the
   request passes the `auth` and `role:admin,doctor` **middleware**. Logged in as admin →
   allowed.
3. **`PatientController::create()`** returns `view('patients.create')`.
4. **Blade** renders `create.blade.php`, which `@include`s `patients/form.blade.php`
   (empty fields, since `$patient` is null), inside `layouts/app.blade.php` (sidebar +
   topbar). The blood-group `<select>` is built from `BloodGroup::cases()`. The browser
   shows the form.
5. **User fills it in and submits.** Browser sends `POST /patients` with the field values
   and the `@csrf` token.
6. **CSRF check** passes (token matches the session). Middleware (`auth`, `role`) pass
   again.
7. **Routing** sends it to `PatientController@store`. Its signature is
   `store(StorePatientRequest $request)` — so Laravel instantiates the **form request and
   runs validation first**.
   - If invalid: Laravel redirects back to `/patients/create` with errors + old input;
     `@error`/`old()` in the form display them. The controller body never runs. *Done.*
   - If valid: continue.
8. **`store()` runs.** It calls
   `$this->patientService->createPatient($request->validated(), auth()->id())`.
9. **`PatientService::createPatient()`** generates the ID
   (`generatePatientId()` → asks `PatientRepository::countByPatientIdPrefix()` →
   `ACH-20260607-0001`), sets `registered_by`, and calls
   `PatientRepository::create($data)`.
10. **`PatientRepository::create()`** (inherited from `BaseRepository`) calls
    `Patient::create($data)`. **Eloquent** writes the row to MySQL, respecting
    `#[Fillable]`. A `Patient` object comes back up the chain.
11. **Back in the controller**, we
    `redirect()->route('patients.show', $patient)->with('success', '... ID: ACH-…')`.
    Browser receives a 302 redirect.
12. **Browser follows** to `GET /patients/{id}`. Routing → `PatientController@show`.
    **Route-model binding** loads the `Patient` by id. The controller returns
    `patients.show`.
13. **Blade** renders the detail page; the **layout** sees the flashed `success` message
    and shows the green-ish banner: *"Patient registered successfully. ID: ACH-20260607-0001."*

Every layer did its one job. That's the whole philosophy in a single flow.

---

## 19. How to run, test, and extend

### Run it (two ways)

```bash
# Simple
php artisan serve            # http://localhost:8000

# Via the wayne Apache CLI (HTTPS .test domain)
wayne serve africhart-emr
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
wayne open africhart-emr     # https://africhart-emr.test
```

A root `.htaccess` rewrites requests into `public/` so Laravel works under wayne's
project-root DocumentRoot (wayne points Apache at the project folder, but Laravel's entry
point is `public/index.php`).

### Reset the database

```bash
php artisan migrate:fresh --seed   # drop, re-migrate, re-seed (back to 2 users + 25 patients)
```

### Inspect things

```bash
php artisan route:list             # all routes
php artisan tinker                 # a REPL to poke models: App\Models\Patient::count()
```

### Helpful artisan generators (for Phase 1)

```bash
php artisan make:model Appointment -m       # model + migration
php artisan make:controller XController
php artisan make:request StoreXRequest
php artisan make:middleware XMiddleware
```

### To add a new feature, follow the layers

Say you add "appointments". You'd: write a migration + `Appointment` model → an
`AppointmentRepository` (queries) → an `AppointmentService` (rules) → a form request
(validation) → a thin `AppointmentController` → routes → Blade views. Same pattern every
time — that consistency is the point.

---

## 20. Glossary

- **Eloquent** — Laravel's ORM; lets you use DB rows as PHP objects (models).
- **ORM** — Object-Relational Mapper; bridges database tables and objects.
- **Migration** — versioned script that builds/changes DB schema.
- **Seeder** — script that inserts demo/initial data.
- **Middleware** — code that runs before a controller (auth, role checks).
- **Service container / dependency injection** — Laravel auto-creates and supplies the
  objects your classes ask for in their constructors.
- **Route-model binding** — type-hinting a model in a controller auto-loads it from the
  URL id.
- **Blade** — Laravel's HTML template engine (`{{ }}`, `@if`, components).
- **Blade component** — reusable UI tag (`<x-stat-card />`).
- **Form Request** — class that validates input before the controller.
- **Mass assignment** — creating a model from an array; controlled by `#[Fillable]`.
- **Cast** — auto-converting a DB value to a richer type (enum, date, hashed).
- **Accessor** — a computed/virtual model property (`getAgeAttribute` → `$patient->age`).
- **Relationship** — a link between models (`belongsTo`, `hasMany`).
- **CSRF token** — hidden form token proving the request came from your site.
- **Session** — server-side per-user store (keeps you logged in across requests).
- **Flash message** — one-request session value (the success banner).
- **PRG (Post/Redirect/Get)** — redirect after a successful POST to avoid resubmits.
- **Pagination** — splitting results into pages (`->paginate(15)`).
- **Eager loading (`with`)** — fetch related rows up front to avoid the N+1 problem.
- **Enum** — a type with a fixed set of values (`UserRole`, `BloodGroup`).
- **Tailwind utility classes** — small single-purpose CSS classes you compose in markup.
- **Vite** — the tool that compiles CSS/JS assets.

---

# Part 2 — Auth flows & interactive UX (Phase 0.5)

This part adds self-service registration, email verification, password reset, and the
app's first JavaScript (Alpine.js) for modals, toasts, and a password toggle.

## 21. Registration with invite codes

**Files:** `app/Http/Controllers/RegisterController.php`,
`app/Http/Requests/RegisterRequest.php`, `config/registration.php`,
`resources/views/auth/register.blade.php`

A clinic shouldn't let the public create accounts, but seeding every user by hand is
tedious. The middle ground: **invite codes**. `config/registration.php` reads two secret
codes from `.env`:

```php
'codes' => [
    'admin'  => env('REGISTER_CODE_ADMIN'),
    'doctor' => env('REGISTER_CODE_DOCTOR'),
],
```

The register page (`auth/register.blade.php`) has **Doctor / Admin tabs** driven by Alpine
(`x-data="{ role: 'doctor' }"`). The active tab sets a hidden `role` field. `RegisterRequest`
validates that the submitted `invite_code` equals the configured code *for that role*:

```php
'invite_code' => ['required', 'string', function ($attr, $value, $fail) {
    $expected = config('registration.codes')[$this->input('role')] ?? null;
    if (! $expected || ! hash_equals($expected, (string) $value)) {
        $fail('Invalid invite code for the selected role.');
    }
}],
```

So the code is **authoritative**: you can only become an admin if you hold the admin code.
`hash_equals` compares in constant time (avoids timing attacks). On success the controller
creates the user, sends a verification code, logs them in, and redirects to the verify page.

## 22. Email verification by code

**Files:** `app/Models/User.php` (implements `MustVerifyEmail`),
`app/Services/EmailVerificationService.php`, `app/Notifications/EmailVerificationCode.php`,
`app/Http/Controllers/EmailVerificationController.php`, migration adding
`email_verification_code` + `..._expires_at`, `resources/views/auth/verify-email.blade.php`

Laravel's built-in verification emails a *signed link*. We wanted a *6-digit code* instead,
so we keep Laravel's plumbing but swap the mechanism:

- `User implements MustVerifyEmail` — this makes the **`verified` middleware** redirect any
  user whose `email_verified_at` is null to the verify page. We override
  `sendEmailVerificationNotification()` so the framework sends our code, not a link.
- `EmailVerificationService::sendCode()` generates `random_int(100000, 999999)`, stores it
  on the user with a 10-minute expiry, and sends it via the `EmailVerificationCode`
  **notification** (a class describing an email; `$user->notify(...)` mails it through SMTP).
- `verify()` checks the code matches and hasn't expired (again with `hash_equals`), then sets
  `email_verified_at` and clears the code.

The routes split into two groups: `auth` (logout + the verify pages — reachable while
*unverified*) and `auth` **+ `verified`** (dashboard + patients — require a verified email).
That's why an unverified user is bounced to the code page until they confirm.

> Mail is real SMTP (see `.env` `MAIL_*`). Port 465 needs `MAIL_ENCRYPTION=ssl`, and the
> `MAIL_FROM_ADDRESS` must be an address the SMTP account owns, or the server rejects it.

## 23. Password reset

**Files:** `app/Http/Controllers/PasswordResetController.php`,
`app/Http/Requests/{PasswordResetLinkRequest,NewPasswordRequest}.php`,
`resources/views/auth/{forgot-password,reset-password}.blade.php`

This uses Laravel's built-in **`Password` broker** (no custom storage — the
`password_reset_tokens` table ships with Laravel). `Password::sendResetLink()` emails a
tokenised link; `Password::reset()` validates the token and runs a callback that sets the
new (hashed) password. We always show "if that email exists, a link is on its way" to avoid
revealing which emails are registered.

## 24. Alpine.js: the interactive layer

**Files:** `resources/js/app.js`, `resources/views/components/{toast,modal,password-input}.blade.php`

Alpine is a tiny JS framework you drive with HTML attributes (`x-data`, `x-show`, `@click`).
`app.js` imports it, registers a global **toast store**, and a **`patientModal`** component.

- **Toasts** (`components/toast.blade.php`): a fixed stack bound to `$store.toasts.items`.
  Anything calls `window.toast('success', 'msg')`; toasts auto-dismiss after 4s. The layout
  bridges server flash → toast: on load it runs `window.toast('success', @js(session('success')))`,
  so every `->with('success', ...)` from a controller appears as a toast (this replaced the
  old static banner).
- **Modal** (`components/modal.blade.php`): a reusable dialog opened by dispatching a window
  event — `$dispatch('open-modal', 'logout')`. Used for the logout confirmation in the topbar.
- **Password toggle** (`components/password-input.blade.php`): a label + password field + eye
  button. `x-data="{ show: false }"` and `:type="show ? 'text' : 'password'"` flip visibility.
  Used on login, register, and reset.

`[x-cloak]` (hidden via a CSS rule) keeps modals/elements invisible until Alpine initialises,
preventing a flash of unstyled content.

## 25. Patient forms in modals (fetch + JSON)

**Files:** `resources/views/patients/index.blade.php`,
`resources/views/components/patient-table.blade.php`,
`app/Http/Controllers/PatientController.php`, `bootstrap/app.php`

On the patients page, "Register Patient" and each row's "Edit" open a modal instead of a new
page. The `patientModal` Alpine component submits with **`fetch`**:

- It sends `Accept: application/json` + the CSRF token (read from the `<meta name="csrf-token">`).
- On **422** it reads `errors` from the JSON and shows them inline in the modal.
- On **success** the controller returns `{ redirect: ... }` (and flashes the success message),
  and the component does `window.location = redirect` — so the next page shows the toast.

For this to work, two things were needed:
1. `PatientController::store/update` return JSON when `$request->wantsJson()`, else redirect
   (so the no-JS fallback pages `patients/create` + `patients/edit` still work — *progressive
   enhancement*).
2. `bootstrap/app.php`'s `shouldRenderJsonWhen(...)` was widened to
   `$request->is('api/*') || $request->expectsJson()`. Previously it forced *all* web
   validation errors to redirect (HTML), so the modal never received JSON 422s. This one line
   is what makes inline modal validation work while leaving normal form posts redirecting.

*This is the trickiest interaction in the app: a server-rendered form, an Alpine component,
and a controller that speaks both HTML and JSON depending on who's asking.*

---

# Part 3 — Feedback, alerts & responsiveness (Phase 0.6)

This part polishes the experience: louder toasts (incl. login/logout), email alerts to
admins on activity, loading states on every action, and a fully responsive layout.

## 26. Toasts everywhere (incl. login/logout)

**Files:** `resources/views/components/toast.blade.php`, `app/Http/Controllers/AuthController.php`

The toast component (built in §24) was made more prominent — a drop shadow, a colored
left-border by type (ink for success, red for error), and bolder text. Because the layout
bridges `session('success')` → a toast, adding a toast to *any* action is now just a
one-liner in the controller. Login and logout got theirs:

```php
// login()
return redirect()->intended(route('dashboard'))
    ->with('success', 'Welcome back, '.$request->user()->name.'!');

// logout()
return redirect()->route('login')->with('success', 'You have been logged out.');
```

The login toast appears on the dashboard (the page we land on); the logout toast appears on
the login page — both because the flash survives exactly one redirect and each destination's
layout renders toasts.

## 27. Email alerts to admins

**Files:** `app/Notifications/AdminActivity.php`, `app/Services/AdminNotifier.php`,
and calls in `PatientController`, `RegisterController`, `EmailVerificationController`

Admins get emailed when something noteworthy happens: a patient is registered or updated, a
new staff account registers, or someone verifies their email.

- **`AdminActivity`** is a *generic* mail notification — it takes a subject, heading, and a
  few lines, so one class covers all four events (a notification is just a class describing
  a message; `$user->notify(...)` / `Notification::send(...)` delivers it).
- **`AdminNotifier`** is a small service with one method per event. Each builds an
  `AdminActivity` and sends it to **all admin users, excluding the actor** (so an admin who
  registers a patient doesn't email themselves):

  ```php
  $admins = User::where('role', 'admin')
      ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
      ->get();
  Notification::send($admins, $notification);
  ```

Two deliberate design choices:

1. **The calls live in controllers, not the service/seeder.** That's why seeding 25 patients
   sends **zero** emails — the seeder calls `PatientService::createPatient()` directly,
   bypassing the controller where the notification fires. Only real HTTP actions notify.
2. **Failures are swallowed.** The whole send is wrapped in `try/catch` that logs and moves
   on, so a flaky SMTP server can never turn a successful patient registration into a 500.

> Email sends **synchronously** here (no queue worker configured), so an action that emails
> waits ~1–2s for SMTP. The loading spinners (next section) cover that wait. In production
> you'd move this to a queue.

## 28. Loading states

**Files:** `resources/views/components/{spinner,submit-button}.blade.php`, every auth form,
the logout modal, the patient modal

Two small reusable pieces:

- **`<x-spinner>`** — an SVG with Tailwind's `animate-spin`.
- **`<x-submit-button>`** — a submit button that reads a shared Alpine `loading` flag,
  disables itself, and swaps its label for the spinner. It works because each `<form>`
  declares `x-data="{ loading: false }" @submit="loading = true"`, and the button (a child)
  inherits that scope:

  ```blade
  <form ... x-data="{ loading: false }" @submit="loading = true">
      ...
      <x-submit-button loadingText="Signing in…" class="...">Sign in</x-submit-button>
  </form>
  ```

The patient **modal** doesn't use a real `<form>` submit (it's `fetch`), so it uses its own
`processing` flag from the `patientModal` component to show the spinner instead.

## 29. Responsive layout

**Files:** `resources/views/layouts/app.blade.php`, `components/sidebar.blade.php`,
`components/topbar.blade.php`

The desktop sidebar becomes a mobile **off-canvas drawer**. One shared Alpine state on the
layout root coordinates it:

```blade
<div x-data="{ sidebarOpen: false }" class="min-h-screen md:flex">
```

- **Sidebar** is `fixed ... -translate-x-full` (off-screen) on mobile and slides in via
  `:class="{ 'translate-x-0': sidebarOpen }"`; on `md+` it's `md:sticky md:translate-x-0`
  (always visible). A semi-transparent backdrop (`md:hidden`) covers the page when open, and
  tapping it — or a nav link, or the close button — sets `sidebarOpen = false`.
- **Topbar** shows a hamburger (`md:hidden`) that sets `sidebarOpen = true`.
- Spacing scales with breakpoints (`p-4 sm:p-6 lg:p-8`); tables already scroll horizontally,
  and the form/dashboard grids already stack on small screens.

Because `sidebarOpen` lives on the layout root, both the sidebar and the topbar (nested
inside it) share the same Alpine scope without any extra wiring.

## 30. Running under Apache (the `storage` permissions gotcha)

Not code, but essential operational knowledge. Serving via the `wayne` CLI runs the app as
Apache's **`www-data`** user, but `wayne serve` chowns the whole project to **you**. Laravel
needs to write compiled views, sessions, cache, and logs into `storage/` and
`bootstrap/cache/` — if `www-data` can't write there you get:

```
tempnam(): file created in the system's temporary directory   (HTTP 500)
```

Fix after every `wayne serve`:

```bash
chmod -R 777 storage bootstrap/cache          # quick, no sudo (you own the files)
# or, cleaner (shares with the www-data group):
sudo chown -R $USER:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache
php artisan view:clear
```

See the README's Troubleshooting table for other common issues.

---

# Part 4 — Shipping it: database dumps & deployment

## 31. Migrations vs a SQL dump

Two different things that both describe the database:

- **Migrations** (`database/migrations/`) are the *source of truth* for the schema — PHP
  that builds tables, versioned in git, reversible, DB-agnostic. You run them with
  `php artisan migrate`.
- **A SQL dump** (`database/schema/*.sql`) is a *snapshot* — raw `CREATE TABLE` + `INSERT`
  statements you can import straight into MySQL (phpMyAdmin, `mysql <db> < file.sql`). Handy
  when a server can't run `artisan`, or to hand someone a ready-to-go database.

We keep both: `africhart_emr.sql` (structure **+** the 2 demo users and 25 patients, plus the
`migrations` rows so Laravel knows they've run) and `africhart_emr-structure.sql` (empty).
Regenerate with `mysqldump`. See the README's "Database dumps" section.

## 32. Deploying to shared hosting (cPanel)

The live demo runs on cPanel shared hosting — a constrained environment (no root, you pick
PHP versions and extensions through a UI, Composer/Node may be limited). The essentials:

- **PHP 8.3** — selected per-domain in MultiPHP Manager, with the right extensions enabled.
  The CLI default is often older, which is why `composer` first complained about the PHP
  version; you point it at the 8.3 binary under `/opt/cpanel/ea-php83/...`.
- **Dependencies** — normally `composer install --no-dev` (for `vendor/`) and a build step
  (for `public/build/`). Both are git-ignored, so a fresh clone lacks them.
- **The git-artifact trick we used** — because this host couldn't build, we temporarily
  *force-committed* `vendor/` + `public/build/` (`git add -f`, bypassing `.gitignore`),
  pushed, pulled them on the server, then **squashed history back to a single clean commit**
  (an orphan branch, force-pushed) so the public repo stays artifact-free and `.env` never
  enters git. The catch: the server's clone must not be hard-reset/re-pulled afterward, or it
  would lose those untracked folders. *This is a pragmatic shortcut for a constrained host —
  the "proper" path is Composer + a build step on the server (or a CI pipeline).*
- **Web root** — must resolve to `public/`. Either set the domain's document root there, or
  let the committed root `.htaccess` rewrite into it (the same trick used for the local
  `wayne` setup in §30).
- **`.env`** — created on the server, never committed; production means `APP_ENV=production`,
  `APP_DEBUG=false`, real DB/mail creds (watch for trailing spaces!).

Full step-by-step commands live in the README's "Deployment" section.

---

*That's the whole app — build, behaviour, and deployment. Read it once start-to-finish, then
open each file next to its section above; by the end you'll know exactly why every line is
there.*
