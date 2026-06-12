# AfriChart EMR — Phase 1 Technical Build Guide
## Full MVP Build · Developer Reference Document

**Project:** AfriChart Technologies EMR — Phase 1 Full MVP
**Developer:** Michael Aleruchi Mgbah / Lymora Tech
**Client:** AfriChart Technologies
**Budget:** ₦500,000 (3 milestones)
**Timeline:** 20–25 days from Phase 0 approval
**Date:** June 2026
**Builds on:** Phase 0 + Phase 0.5 + Phase 0.6 (complete codebase)

---

## Table of Contents

### Part 1 — Strategy & Architecture
1. [Phase 1 Overview: What's Contracted vs What We Ship](#1-phase-1-overview)
2. [Architecture Decisions for Phase 1](#2-architecture-decisions)
3. [New Database Schema](#3-new-database-schema)

### Part 2 — Role Expansion
4. [Expanding to 4 Roles](#4-expanding-to-4-roles)
5. [Role-Based Dashboards](#5-role-based-dashboards)

### Part 3 — Core Modules
6. [Consultation & Clinical Notes Module](#6-consultation--clinical-notes-module)
7. [Prescription Recording Module](#7-prescription-recording-module)
8. [Billing & Invoice Generation Module](#8-billing--invoice-generation-module)

### Part 4 — REST API
9. [API Layer Architecture](#9-api-layer-architecture)
10. [API Endpoints Reference](#10-api-endpoints-reference)
11. [API Authentication & Documentation](#11-api-authentication--documentation)

### Part 5 — Overdelivery Features (The "Wow")
12. [Patient Timeline & Visit History](#12-patient-timeline--visit-history)
13. [Audit Trail System](#13-audit-trail-system)
14. [PDF Invoice Generation](#14-pdf-invoice-generation)
15. [Dashboard Analytics with Charts](#15-dashboard-analytics-with-charts)
16. [Data Export (CSV)](#16-data-export-csv)
17. [Print-Friendly Views](#17-print-friendly-views)
18. [Patient Queue / Waiting List](#18-patient-queue--waiting-list)

### Part 6 — Execution
19. [Milestone Delivery Map](#19-milestone-delivery-map)
20. [Day-by-Day Build Plan](#20-day-by-day-build-plan)
21. [Testing Checklist](#21-testing-checklist)
22. [Deployment & Handover](#22-deployment--handover)

---

# Part 1 — Strategy & Architecture

## 1. Phase 1 Overview

### What the Contract Requires

From Section 3.2 of the signed agreement, Phase 1 adds these on top of Phase 0:

| Feature | Status After Phase 0 |
|---|---|
| Authentication | ✅ Done (login, register, verify, reset) |
| RBAC — 4 roles (Admin, Doctor, Nurse, Receptionist) | 🔶 Partial — 2 roles done, need Nurse + Receptionist |
| Role-Based UI — dedicated dashboards per role | 🔶 Partial — Admin + Doctor done, need Nurse + Receptionist |
| Patient Registration | ✅ Done |
| Patient Management | ✅ Done |
| Patient Listing | ✅ Done |
| Consultation & Clinical Notes | 🆕 New module |
| Prescription Recording | 🆕 New module |
| Billing & Invoice Generation | 🆕 New module |
| Clean REST API | 🆕 New layer |
| AfriChart Branding | ✅ Done |
| Demo Environment | ✅ Done |
| Handover Package | 🔶 Needs updating |

### What We're Actually Shipping (Overdelivery Strategy)

The contract gets the client what they asked for. The overdelivery makes them feel like they got ₦800k worth of work for ₦500k. Every "extra" feature below takes minimal extra time because it builds on architecture we've already established — but to the client, each one feels like a significant addition.

| Overdelivery Feature | Why It Wows | Effort |
|---|---|---|
| Patient Timeline (visit history view) | Client can demo to clinics — "look at the full patient journey" | Medium |
| Audit Trail (who did what, when) | Healthcare compliance. Shows you think about security | Medium |
| PDF Invoice Generation | Clinics can print/email invoices. Tangible output | Low |
| Dashboard Analytics (charts) | Visual impact. Admin dashboard goes from stats to insights | Medium |
| Data Export (CSV) | "Can I download this?" — every clinic admin asks this | Low |
| Print-Friendly Views | Patient records, invoices — basic but always forgotten | Low |
| Patient Queue / Waiting List | Real clinical workflow. Receptionist checks in, doctor sees queue | Medium |

**Rule: every overdelivery feature is something the client can show a clinic prospect during a sales demo.** That's the test. If it doesn't help sell AfriChart to a real clinic, skip it.

---

## 2. Architecture Decisions

### Existing Patterns (Carry Forward)

Phase 0 established these. Phase 1 follows them exactly — consistency is non-negotiable.

```
Route → Controller → Service → Repository → Model → Database
```

- **Controllers:** Thin. Handle HTTP. Return views or JSON.
- **Services:** Business logic. ID generation, billing calculations, consultation workflows.
- **Repositories:** Database queries. Search, filter, paginate, aggregate.
- **Models:** Eloquent relationships, casts, accessors.
- **Form Requests:** Validation. Extend `FormRequest` (web) or `BaseRequest` (API).
- **Enums:** Type-safe fixed values.
- **Blade Components:** Reusable UI pieces.

### New Patterns for Phase 1

**1. Policy Classes (Authorization)**

With 4 roles, simple middleware isn't enough. You need granular rules like "only the doctor who created a consultation can edit it" or "nurses can view but not create prescriptions." Laravel Policies handle this cleanly.

```bash
php artisan make:policy ConsultationPolicy --model=Consultation
```

```php
// app/Policies/ConsultationPolicy.php
class ConsultationPolicy
{
    public function update(User $user, Consultation $consultation): bool
    {
        // Only the doctor who created it, or an admin
        return $user->id === $consultation->doctor_id || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        // Doctors and admins can see all consultations
        // Nurses can see consultations for patients they're assigned to
        return $user->isAdmin() || $user->isDoctor() || $user->isNurse();
    }
}
```

Register in `AppServiceProvider` or `AuthServiceProvider`. Use in controllers:

```php
$this->authorize('update', $consultation);
```

**2. Event/Listener Pattern (for Audit Trail + Notifications)**

When a consultation is created, multiple things should happen: log the audit trail, notify admins, potentially update the patient timeline. Instead of stuffing all that into the controller, use events:

```php
// In ConsultationService after creating:
event(new ConsultationCreated($consultation));

// Listeners (registered in EventServiceProvider):
// - LogAuditTrail
// - NotifyAdminsOfConsultation
```

This keeps the service clean and makes adding new side-effects trivial.

**3. API Resources (JSON Transformation)**

For the REST API, don't return raw models. Use API Resources to control the JSON structure:

```bash
php artisan make:resource PatientResource
php artisan make:resource ConsultationResource
```

```php
// app/Http/Resources/PatientResource.php
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'patient_id'    => $this->patient_id,
            'full_name'     => $this->full_name,
            'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
            'age'           => $this->age,
            'phone'         => $this->phone,
            'blood_group'   => $this->blood_group->value,
            'allergies'     => $this->allergies,
            'registered_by' => new UserResource($this->whenLoaded('registeredBy')),
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
        ];
    }
}
```

**4. Trait: `HasAuditTrail`**

A model trait that auto-logs create/update/delete operations. Apply it to any model that needs tracking:

```php
// app/Traits/HasAuditTrail.php
trait HasAuditTrail
{
    protected static function bootHasAuditTrail(): void
    {
        static::created(fn ($model) => AuditLog::record('created', $model));
        static::updated(fn ($model) => AuditLog::record('updated', $model));
        static::deleted(fn ($model) => AuditLog::record('deleted', $model));
    }
}
```

---

## 3. New Database Schema

### Entity Relationship Diagram

```
users (1) ─────────── (many) patients
  │                            │
  │                            ├──── (many) consultations
  │                            │              │
  │                            │              ├──── (many) prescriptions
  │                            │              │
  │                            │              └──── (1) invoice
  │                            │
  │                            └──── (many) queue_entries
  │
  └──── (many) audit_logs
```

### New Tables

#### consultations

```php
Schema::create('consultations', function (Blueprint $table) {
    $table->id();

    // Relationships
    $table->foreignId('patient_id')->constrained()->onDelete('restrict');
    $table->foreignId('doctor_id')->constrained('users')->onDelete('restrict');

    // Clinical data
    $table->text('chief_complaint');           // Why the patient came in
    $table->text('clinical_notes');            // Doctor's observations
    $table->text('diagnosis')->nullable();     // Diagnosis (may be pending)
    $table->text('plan')->nullable();          // Treatment plan
    $table->enum('status', ['in_progress', 'completed', 'follow_up'])
          ->default('in_progress');

    // Vitals (captured by nurse or doctor)
    $table->decimal('temperature', 4, 1)->nullable();      // °C (e.g. 37.5)
    $table->string('blood_pressure', 10)->nullable();       // e.g. "120/80"
    $table->integer('pulse_rate')->nullable();               // bpm
    $table->decimal('weight', 5, 1)->nullable();            // kg
    $table->decimal('height', 5, 1)->nullable();            // cm
    $table->text('vitals_notes')->nullable();               // Additional vitals observations

    $table->string('consultation_id', 25)->unique();  // ACH-C-YYYYMMDD-XXXX
    $table->timestamps();

    $table->index('patient_id');
    $table->index('doctor_id');
    $table->index('status');
    $table->index('consultation_id');
    $table->index('created_at');
});
```

#### prescriptions

```php
Schema::create('prescriptions', function (Blueprint $table) {
    $table->id();

    // Relationships
    $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
    $table->foreignId('patient_id')->constrained()->onDelete('restrict');
    $table->foreignId('prescribed_by')->constrained('users')->onDelete('restrict');

    // Medication details
    $table->string('medication_name');          // Drug name
    $table->string('dosage');                   // e.g. "500mg"
    $table->string('frequency');                // e.g. "3 times daily"
    $table->string('duration');                 // e.g. "7 days"
    $table->string('route')->default('oral');   // oral, IV, topical, etc.
    $table->text('instructions')->nullable();   // Special instructions
    $table->integer('quantity')->nullable();     // Number of units dispensed

    $table->timestamps();

    $table->index('consultation_id');
    $table->index('patient_id');
});
```

#### invoices

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();

    // Relationships
    $table->foreignId('patient_id')->constrained()->onDelete('restrict');
    $table->foreignId('consultation_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('created_by')->constrained('users')->onDelete('restrict');

    // Invoice details
    $table->string('invoice_number', 25)->unique();  // ACH-INV-YYYYMMDD-XXXX
    $table->decimal('subtotal', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('total', 12, 2)->default(0);
    $table->enum('status', ['draft', 'issued', 'paid', 'partially_paid', 'cancelled'])
          ->default('draft');
    $table->enum('payment_method', ['cash', 'transfer', 'card', 'insurance', 'other'])
          ->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->text('notes')->nullable();

    $table->timestamps();

    $table->index('patient_id');
    $table->index('invoice_number');
    $table->index('status');
    $table->index('created_at');
});
```

#### invoice_items

```php
Schema::create('invoice_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
    $table->string('description');              // "Consultation Fee", "Paracetamol 500mg x 20"
    $table->decimal('unit_price', 10, 2);
    $table->integer('quantity')->default(1);
    $table->decimal('amount', 12, 2);           // unit_price × quantity
    $table->string('category')->default('service'); // service, medication, lab, other

    $table->timestamps();
});
```

#### patient_queue

```php
Schema::create('patient_queue', function (Blueprint $table) {
    $table->id();

    $table->foreignId('patient_id')->constrained()->onDelete('cascade');
    $table->foreignId('checked_in_by')->constrained('users')->onDelete('restrict');
    $table->foreignId('assigned_doctor_id')->nullable()->constrained('users')->onDelete('set null');

    $table->enum('status', ['waiting', 'in_consultation', 'completed', 'cancelled'])
          ->default('waiting');
    $table->integer('queue_number');            // Daily sequential: 1, 2, 3...
    $table->text('reason')->nullable();         // Brief reason for visit
    $table->timestamp('checked_in_at');
    $table->timestamp('seen_at')->nullable();   // When doctor started consultation
    $table->timestamp('completed_at')->nullable();

    $table->timestamps();

    $table->index(['status', 'created_at']);
    $table->index('checked_in_at');
});
```

#### audit_logs

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('user_name');                // Snapshot of name at time of action
    $table->string('action');                   // created, updated, deleted
    $table->string('model_type');               // App\Models\Patient, etc.
    $table->unsignedBigInteger('model_id');
    $table->string('description');              // Human-readable: "Registered patient Chioma Nwosu"
    $table->json('old_values')->nullable();     // Before state (for updates)
    $table->json('new_values')->nullable();     // After state
    $table->string('ip_address', 45)->nullable();

    $table->timestamp('created_at');

    $table->index(['model_type', 'model_id']);
    $table->index('user_id');
    $table->index('created_at');
});
```

### New Enums

```php
// app/Enums/UserRole.php — UPDATED
enum UserRole: string
{
    case Admin        = 'admin';
    case Doctor       = 'doctor';
    case Nurse        = 'nurse';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Admin        => 'Admin',
            self::Doctor       => 'Doctor',
            self::Nurse        => 'Nurse',
            self::Receptionist => 'Receptionist',
        };
    }
}

// app/Enums/ConsultationStatus.php
enum ConsultationStatus: string
{
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case FollowUp   = 'follow_up';

    public function label(): string { ... }
    public function color(): string { ... } // Tailwind color class for badges
}

// app/Enums/InvoiceStatus.php
enum InvoiceStatus: string
{
    case Draft        = 'draft';
    case Issued       = 'issued';
    case Paid         = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Cancelled    = 'cancelled';

    public function label(): string { ... }
    public function color(): string { ... }
}

// app/Enums/PaymentMethod.php
enum PaymentMethod: string
{
    case Cash      = 'cash';
    case Transfer  = 'transfer';
    case Card      = 'card';
    case Insurance = 'insurance';
    case Other     = 'other';

    public function label(): string { ... }
}

// app/Enums/QueueStatus.php
enum QueueStatus: string
{
    case Waiting         = 'waiting';
    case InConsultation  = 'in_consultation';
    case Completed       = 'completed';
    case Cancelled       = 'cancelled';

    public function label(): string { ... }
    public function color(): string { ... }
}

// app/Enums/MedicationRoute.php
enum MedicationRoute: string
{
    case Oral       = 'oral';
    case IV         = 'iv';
    case IM         = 'im';
    case Topical    = 'topical';
    case Sublingual = 'sublingual';
    case Rectal     = 'rectal';
    case Inhaled    = 'inhaled';

    public function label(): string { ... }
}
```

---

# Part 2 — Role Expansion

## 4. Expanding to 4 Roles

### Migration

```bash
php artisan make:migration update_user_roles
```

No schema change needed — the `role` column is already a string. You just update the `UserRole` enum and add new helpers to the User model:

```php
// app/Models/User.php — add these
public function isNurse(): bool
{
    return $this->role === UserRole::Nurse;
}

public function isReceptionist(): bool
{
    return $this->role === UserRole::Receptionist;
}

public function isClinicalStaff(): bool
{
    return in_array($this->role, [UserRole::Doctor, UserRole::Nurse]);
}
```

### Updated Seeders

Add Nurse and Receptionist demo accounts:

```php
// UserSeeder.php — add
User::create([
    'name'  => 'Nurse Amina',
    'email' => 'nurse@africhart.com',
    'password' => 'password',
    'role'  => 'nurse',
])->forceFill(['email_verified_at' => now()])->save();

User::create([
    'name'  => 'Front Desk — Chioma',
    'email' => 'reception@africhart.com',
    'password' => 'password',
    'role'  => 'receptionist',
])->forceFill(['email_verified_at' => now()])->save();
```

### Updated Invite Codes

```env
REGISTER_CODE_ADMIN="ACH-ADMIN-7F3K9X"
REGISTER_CODE_DOCTOR="ACH-DOCTOR-2M8QW4"
REGISTER_CODE_NURSE="ACH-NURSE-5P9KT2"
REGISTER_CODE_RECEPTIONIST="ACH-RECEP-8N4JL6"
```

Update `config/registration.php` and the registration form to include Nurse and Receptionist tabs.

### Role Permission Matrix

This is the core RBAC map. Every feature check traces back to this table.

| Action | Admin | Doctor | Nurse | Receptionist |
|---|---|---|---|---|
| **Dashboard** — view own role dashboard | ✅ | ✅ | ✅ | ✅ |
| **Patients** — register new | ✅ | ✅ | ✅ | ✅ |
| **Patients** — view/edit | ✅ | ✅ | ✅ | ✅ |
| **Queue** — check in patient | ✅ | ❌ | ✅ | ✅ |
| **Queue** — assign doctor | ✅ | ❌ | ✅ | ✅ |
| **Queue** — view waiting list | ✅ | ✅ | ✅ | ✅ |
| **Consultations** — create/start | ✅ | ✅ | ❌ | ❌ |
| **Consultations** — view | ✅ | ✅ | ✅ (read-only) | ❌ |
| **Consultations** — record vitals | ✅ | ✅ | ✅ | ❌ |
| **Consultations** — edit own | ❌ | ✅ (own only) | ❌ | ❌ |
| **Prescriptions** — create | ✅ | ✅ | ❌ | ❌ |
| **Prescriptions** — view | ✅ | ✅ | ✅ | ❌ |
| **Invoices** — create/manage | ✅ | ❌ | ❌ | ✅ |
| **Invoices** — view | ✅ | ✅ | ❌ | ✅ |
| **Invoices** — mark paid | ✅ | ❌ | ❌ | ✅ |
| **Audit Log** — view | ✅ | ❌ | ❌ | ❌ |
| **Data Export** — CSV | ✅ | ❌ | ❌ | ❌ |
| **Settings** — manage invite codes | ✅ | ❌ | ❌ | ❌ |

---

## 5. Role-Based Dashboards

### Admin Dashboard (Enhanced)

The Phase 0 admin dashboard shows 3 stat cards + recent patients. Phase 1 transforms it:

**Stats Row (6 cards in 2 rows of 3):**
- Total Patients (existing)
- Registered Today (existing)
- This Week (existing)
- Today's Consultations (new)
- Pending Invoices (new)
- Revenue This Month (new)

**Charts Section (new):**
- Patient registrations over the last 30 days (line chart)
- Consultations by status (pie/donut chart)
- Revenue by month — last 6 months (bar chart)

**Activity Feed (new):**
- Last 20 audit log entries: "Dr. Emeka completed consultation for Chioma Nwosu", "Nurse Amina recorded vitals for patient ACH-20260607-0003", "Receptionist Chioma issued invoice INV-20260610-0001"

**Quick Actions:**
- Register Patient, View Queue, Export Data

### Doctor Dashboard

**My Queue (primary view):**
- Today's assigned patients with status (waiting → in consultation → completed)
- Click a patient to start/continue consultation

**Stats Row (3 cards):**
- My Consultations Today
- My Consultations This Week
- Patients Seen Total

**Recent Consultations:**
- Last 10 consultations with status badges

### Nurse Dashboard

**Today's Queue (primary view):**
- All waiting patients — click to record vitals
- Vitals entry form (temperature, BP, pulse, weight, height)

**Stats Row (3 cards):**
- Patients Waiting
- Vitals Recorded Today
- Queue Completed Today

### Receptionist Dashboard

**Queue Management (primary view):**
- Check-in form: search patient → assign to queue → assign doctor
- Live queue display with status

**Stats Row (3 cards):**
- Patients Checked In Today
- Pending Invoices
- Payments Received Today

**Pending Invoices:**
- Draft invoices that need issuing
- Quick "Mark as Paid" action

---

# Part 3 — Core Modules

## 6. Consultation & Clinical Notes Module

### The Clinical Workflow

This is how a consultation works in a real Nigerian private clinic. Your code models this flow:

```
Patient arrives → Receptionist checks in (queue)
                → Nurse records vitals
                → Doctor starts consultation
                → Doctor writes notes + diagnosis
                → Doctor adds prescriptions
                → Doctor completes consultation
                → Receptionist generates invoice
                → Patient pays and leaves
```

### Files to Create

```
app/
├── Enums/ConsultationStatus.php
├── Models/Consultation.php
├── Repositories/ConsultationRepository.php
├── Services/ConsultationService.php
├── Http/
│   ├── Controllers/ConsultationController.php
│   ├── Requests/StoreConsultationRequest.php
│   ├── Requests/UpdateConsultationRequest.php
│   ├── Requests/RecordVitalsRequest.php
│   └── Resources/ConsultationResource.php
├── Policies/ConsultationPolicy.php
│
resources/views/
├── consultations/
│   ├── index.blade.php          # All consultations list
│   ├── create.blade.php         # Start new consultation (select patient or from queue)
│   ├── show.blade.php           # Full consultation view (notes + vitals + prescriptions + invoice)
│   ├── edit.blade.php           # Edit consultation (doctor only, own only)
│   └── vitals-form.blade.php    # Vitals entry partial (used by nurse and doctor)
```

### Consultation Model

```php
// app/Models/Consultation.php
#[Fillable([
    'patient_id', 'doctor_id', 'chief_complaint', 'clinical_notes',
    'diagnosis', 'plan', 'status', 'consultation_id',
    'temperature', 'blood_pressure', 'pulse_rate',
    'weight', 'height', 'vitals_notes',
])]
class Consultation extends Model
{
    use HasAuditTrail;

    protected function casts(): array
    {
        return [
            'status'      => ConsultationStatus::class,
            'temperature' => 'decimal:1',
            'weight'      => 'decimal:1',
            'height'      => 'decimal:1',
        ];
    }

    // --- Relationships ---

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    // --- Accessors ---

    public function getBmiAttribute(): ?float
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }
        return null;
    }

    public function getHasVitalsAttribute(): bool
    {
        return $this->temperature || $this->blood_pressure || $this->pulse_rate;
    }
}
```

### Consultation ID Format

`ACH-C-YYYYMMDD-XXXX` — same pattern as patient IDs, with a `C` prefix for consultations.

### ConsultationService Key Methods

```php
class ConsultationService extends BaseService
{
    public function startConsultation(array $data, int $doctorId): Consultation
    {
        $data['consultation_id'] = $this->generateConsultationId();
        $data['doctor_id'] = $doctorId;
        $data['status'] = ConsultationStatus::InProgress;

        $consultation = $this->consultationRepository->create($data);

        // If patient was in queue, update their status
        $this->patientQueueService->markInConsultation($data['patient_id']);

        return $consultation;
    }

    public function completeConsultation(Consultation $consultation): Consultation
    {
        $consultation->update(['status' => ConsultationStatus::Completed]);

        // Update queue
        $this->patientQueueService->markCompleted($consultation->patient_id);

        return $consultation->fresh();
    }

    public function recordVitals(Consultation $consultation, array $vitals): Consultation
    {
        $consultation->update($vitals);
        return $consultation->fresh();
    }
}
```

### Consultation Show Page — The Star Page

The consultation detail page is the most important page in the EMR. It's where the doctor spends most of their time. Design it like a clinical workspace:

```
┌──────────────────────────────────────────────────────────────┐
│  ← Back to consultations          ACH-C-20260610-0003       │
│                                                              │
│  ┌─────────────────────────────┐  ┌────────────────────────┐ │
│  │ PATIENT INFO               │  │ VITALS                 │ │
│  │ Chioma Nwosu  (ACH-..0001) │  │ Temp: 37.2°C          │ │
│  │ Age: 36 · O+ · Allergies:  │  │ BP: 120/80            │ │
│  │ Penicillin                  │  │ Pulse: 72 bpm         │ │
│  │                             │  │ Weight: 68 kg         │ │
│  │                             │  │ BMI: 24.2             │ │
│  └─────────────────────────────┘  └────────────────────────┘ │
│                                                              │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ CLINICAL NOTES                                          │ │
│  │ Chief Complaint: Persistent headache for 3 days...      │ │
│  │ Notes: Patient presents with...                         │ │
│  │ Diagnosis: Tension-type headache                        │ │
│  │ Plan: Prescribe analgesics, advise rest...              │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ PRESCRIPTIONS                              [+ Add]      │ │
│  │ ┌─────────────────────────────────────────────────────┐  │ │
│  │ │ 1. Paracetamol 500mg · Oral · 3x daily · 5 days   │  │ │
│  │ │ 2. Ibuprofen 400mg · Oral · 2x daily · 3 days     │  │ │
│  │ └─────────────────────────────────────────────────────┘  │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │ INVOICE                           [Generate Invoice]    │ │
│  │ Status: Not yet created                                 │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                              │
│  [Complete Consultation]                                     │
└──────────────────────────────────────────────────────────────┘
```

---

## 7. Prescription Recording Module

### Files to Create

```
app/
├── Enums/MedicationRoute.php
├── Models/Prescription.php
├── Repositories/PrescriptionRepository.php
├── Services/PrescriptionService.php
├── Http/
│   ├── Controllers/PrescriptionController.php
│   ├── Requests/StorePrescriptionRequest.php
│   └── Resources/PrescriptionResource.php
```

### Key Design Decision: Prescriptions Are Nested Under Consultations

A prescription always belongs to a consultation. You don't create a prescription in isolation. The URL structure reflects this:

```
/consultations/{consultation}/prescriptions          # List for this consultation
/consultations/{consultation}/prescriptions/create   # Add a prescription
/consultations/{consultation}/prescriptions/{id}     # View one
```

### Prescription Form (Dynamic Add)

The doctor should be able to add multiple prescriptions without leaving the consultation page. Use Alpine.js with a repeatable form:

```javascript
Alpine.data('prescriptionForm', () => ({
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', route: 'oral', instructions: '', quantity: '' }],

    addItem() {
        this.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', route: 'oral', instructions: '', quantity: '' });
    },

    removeItem(index) {
        if (this.items.length > 1) this.items.splice(index, 1);
    },

    async submitAll() { ... }
}));
```

### Validation

```php
class StorePrescriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'medication_name' => ['required', 'string', 'max:255'],
            'dosage'          => ['required', 'string', 'max:100'],
            'frequency'       => ['required', 'string', 'max:100'],
            'duration'        => ['required', 'string', 'max:100'],
            'route'           => ['required', new Enum(MedicationRoute::class)],
            'instructions'    => ['nullable', 'string', 'max:500'],
            'quantity'        => ['nullable', 'integer', 'min:1'],
        ];
    }
}
```

### Common Medication Presets (Quality Touch)

Store a JSON file of common Nigerian clinic medications with standard dosages. Populate the form with autocomplete suggestions:

```json
// storage/app/data/medications.json
[
    { "name": "Paracetamol", "dosages": ["500mg", "1000mg"], "routes": ["oral"], "common_frequency": "3 times daily" },
    { "name": "Amoxicillin", "dosages": ["250mg", "500mg"], "routes": ["oral"], "common_frequency": "3 times daily" },
    { "name": "Ibuprofen", "dosages": ["200mg", "400mg"], "routes": ["oral"], "common_frequency": "2–3 times daily" },
    { "name": "Metformin", "dosages": ["500mg", "850mg", "1000mg"], "routes": ["oral"], "common_frequency": "2 times daily" },
    { "name": "Amlodipine", "dosages": ["5mg", "10mg"], "routes": ["oral"], "common_frequency": "Once daily" },
    { "name": "Ciprofloxacin", "dosages": ["250mg", "500mg"], "routes": ["oral"], "common_frequency": "2 times daily" },
    { "name": "Omeprazole", "dosages": ["20mg", "40mg"], "routes": ["oral"], "common_frequency": "Once daily" },
    { "name": "Metronidazole", "dosages": ["200mg", "400mg"], "routes": ["oral", "iv"], "common_frequency": "3 times daily" },
    { "name": "Diclofenac", "dosages": ["25mg", "50mg", "75mg"], "routes": ["oral", "im"], "common_frequency": "2–3 times daily" },
    { "name": "Artemether/Lumefantrine", "dosages": ["20/120mg", "40/240mg"], "routes": ["oral"], "common_frequency": "2 times daily for 3 days" }
]
```

This doesn't lock the doctor in — they can always type a custom medication. But the autocomplete saves time and reduces typos.

---

## 8. Billing & Invoice Generation Module

### Files to Create

```
app/
├── Enums/InvoiceStatus.php
├── Enums/PaymentMethod.php
├── Models/Invoice.php
├── Models/InvoiceItem.php
├── Repositories/InvoiceRepository.php
├── Services/InvoiceService.php
├── Http/
│   ├── Controllers/InvoiceController.php
│   ├── Requests/StoreInvoiceRequest.php
│   ├── Requests/UpdateInvoiceRequest.php
│   ├── Requests/MarkInvoicePaidRequest.php
│   └── Resources/InvoiceResource.php
├── Policies/InvoicePolicy.php
```

### Invoice Number Format

`ACH-INV-YYYYMMDD-XXXX` — same pattern.

### InvoiceService Key Methods

```php
class InvoiceService extends BaseService
{
    /**
     * Generate an invoice from a completed consultation.
     * Auto-adds consultation fee + prescription items.
     */
    public function generateFromConsultation(Consultation $consultation, int $createdBy): Invoice
    {
        $invoice = $this->invoiceRepository->create([
            'patient_id'       => $consultation->patient_id,
            'consultation_id'  => $consultation->id,
            'created_by'       => $createdBy,
            'invoice_number'   => $this->generateInvoiceNumber(),
            'status'           => InvoiceStatus::Draft,
        ]);

        // Auto-add consultation fee as first item
        $this->addItem($invoice, [
            'description' => 'Consultation Fee',
            'unit_price'  => 5000.00, // Default — configurable later
            'quantity'    => 1,
            'category'    => 'service',
        ]);

        // Auto-add prescribed medications
        foreach ($consultation->prescriptions as $prescription) {
            $this->addItem($invoice, [
                'description' => "{$prescription->medication_name} {$prescription->dosage} × {$prescription->quantity}",
                'unit_price'  => 0, // Price entered manually by receptionist
                'quantity'    => $prescription->quantity ?? 1,
                'category'    => 'medication',
            ]);
        }

        $this->recalculateTotals($invoice);

        return $invoice;
    }

    /**
     * Recalculate subtotal/total from line items.
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('amount');
        $total = $subtotal + $invoice->tax - $invoice->discount;

        $invoice->update([
            'subtotal' => $subtotal,
            'total'    => max(0, $total),
        ]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice, string $paymentMethod): Invoice
    {
        $invoice->update([
            'status'         => InvoiceStatus::Paid,
            'payment_method' => $paymentMethod,
            'paid_at'        => now(),
        ]);

        return $invoice->fresh();
    }
}
```

### Invoice UI

The invoice page needs to feel like a real invoice — clean, structured, printable:

```
┌──────────────────────────────────────────────────────────┐
│  AfriChart EMR                                           │
│  ─────────────────────────────────────────────────────── │
│  INVOICE                    ACH-INV-20260610-0001        │
│  Status: [DRAFT]            Date: 10 June 2026           │
│                                                          │
│  Patient: Chioma Nwosu (ACH-20260607-0001)               │
│  Consultation: ACH-C-20260610-0003                       │
│  ─────────────────────────────────────────────────────── │
│                                                          │
│  # │ Description                  │ Qty │ Price │ Amount │
│  ──┼──────────────────────────────┼─────┼───────┼────────│
│  1 │ Consultation Fee             │  1  │ ₦5000 │ ₦5000  │
│  2 │ Paracetamol 500mg × 20      │  1  │ ₦800  │ ₦800   │
│  3 │ Ibuprofen 400mg × 10        │  1  │ ₦600  │ ₦600   │
│  ──┼──────────────────────────────┼─────┼───────┼────────│
│                                     Subtotal:  ₦6,400    │
│                                     Tax:       ₦0        │
│                                     Discount:  ₦0        │
│                                     ─────────────────    │
│                                     TOTAL:     ₦6,400    │
│                                                          │
│  [Edit Items]  [Issue Invoice]  [Mark as Paid]  [Print]  │
└──────────────────────────────────────────────────────────┘
```

### Invoice Item Editing

Receptionist needs to set prices for medications (since prices vary by clinic). Use an inline-editable table — click a price field, type the amount, it auto-saves and recalculates the total. Alpine.js handles this without a page reload.

---

# Part 4 — REST API

## 9. API Layer Architecture

### Why Build the API

The contract specifies "all modules exposed via a well-structured REST API." This serves two purposes:
1. Future mobile app can consume it
2. Third-party integrations (lab systems, pharmacy systems, NHIS)

### Structure

All API routes go in `routes/api.php` under the `/api/v1` prefix. They use Sanctum token auth, your existing `BaseRequest` (which returns JSON 422 on validation failure), and API Resources for response formatting.

```
routes/api.php
├── /api/v1/auth/login          → API token login
├── /api/v1/auth/logout         → Revoke token
├── /api/v1/patients            → CRUD
├── /api/v1/consultations       → CRUD
├── /api/v1/prescriptions       → Scoped under consultations
├── /api/v1/invoices            → CRUD
├── /api/v1/queue               → Today's queue
└── /api/v1/dashboard/stats     → Stats for current user's role
```

### API Controllers

Create separate API controllers (don't reuse web controllers). They share the same services but return JSON instead of views:

```
app/Http/Controllers/Api/V1/
├── AuthController.php
├── PatientController.php
├── ConsultationController.php
├── PrescriptionController.php
├── InvoiceController.php
├── QueueController.php
└── DashboardController.php
```

### Consistent Response Format

Use your existing `ApiResponse` trait. Every response follows:

```json
{
    "success": true,
    "message": "Patient created successfully.",
    "data": { ... }
}
```

For paginated lists:

```json
{
    "success": true,
    "message": "Success",
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    }
}
```

---

## 10. API Endpoints Reference

### Authentication

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| POST | `/api/v1/auth/login` | Get API token | No |
| POST | `/api/v1/auth/logout` | Revoke current token | Yes |
| GET | `/api/v1/auth/user` | Get authenticated user | Yes |

### Patients

| Method | Endpoint | Description | Roles |
|---|---|---|---|
| GET | `/api/v1/patients` | List (paginated, searchable) | All |
| POST | `/api/v1/patients` | Create | All |
| GET | `/api/v1/patients/{id}` | Show | All |
| PUT | `/api/v1/patients/{id}` | Update | All |
| GET | `/api/v1/patients/{id}/timeline` | Visit history | Doctor, Admin |

### Consultations

| Method | Endpoint | Description | Roles |
|---|---|---|---|
| GET | `/api/v1/consultations` | List | Doctor, Admin, Nurse |
| POST | `/api/v1/consultations` | Start consultation | Doctor, Admin |
| GET | `/api/v1/consultations/{id}` | Show (with prescriptions + invoice) | Doctor, Admin, Nurse |
| PUT | `/api/v1/consultations/{id}` | Update notes | Doctor (own), Admin |
| PATCH | `/api/v1/consultations/{id}/vitals` | Record vitals | Doctor, Nurse, Admin |
| PATCH | `/api/v1/consultations/{id}/complete` | Mark completed | Doctor (own), Admin |

### Prescriptions

| Method | Endpoint | Description | Roles |
|---|---|---|---|
| GET | `/api/v1/consultations/{id}/prescriptions` | List for consultation | Doctor, Admin, Nurse |
| POST | `/api/v1/consultations/{id}/prescriptions` | Add prescription | Doctor, Admin |
| DELETE | `/api/v1/prescriptions/{id}` | Remove | Doctor (own), Admin |

### Invoices

| Method | Endpoint | Description | Roles |
|---|---|---|---|
| GET | `/api/v1/invoices` | List | Admin, Receptionist |
| POST | `/api/v1/invoices/from-consultation/{id}` | Generate from consultation | Admin, Receptionist |
| GET | `/api/v1/invoices/{id}` | Show with items | Admin, Receptionist, Doctor |
| PUT | `/api/v1/invoices/{id}` | Update items/prices | Admin, Receptionist |
| PATCH | `/api/v1/invoices/{id}/pay` | Mark as paid | Admin, Receptionist |
| GET | `/api/v1/invoices/{id}/pdf` | Download PDF | All |

### Queue

| Method | Endpoint | Description | Roles |
|---|---|---|---|
| GET | `/api/v1/queue` | Today's queue | All |
| POST | `/api/v1/queue` | Check in patient | Admin, Receptionist, Nurse |
| PATCH | `/api/v1/queue/{id}/assign` | Assign doctor | Admin, Receptionist, Nurse |
| PATCH | `/api/v1/queue/{id}/cancel` | Cancel entry | Admin, Receptionist |

---

## 11. API Authentication & Documentation

### Sanctum Token Auth

```php
// Api/V1/AuthController.php
public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return $this->error('Invalid credentials.', 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return $this->success([
        'user'  => new UserResource($user),
        'token' => $token,
    ], 'Authenticated successfully.');
}
```

### Swagger / OpenAPI Documentation

Your project already has `l5-swagger` installed. Add OpenAPI annotations to the API controllers:

```php
/**
 * @OA\Get(
 *     path="/api/v1/patients",
 *     summary="List patients",
 *     tags={"Patients"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Success")
 * )
 */
```

Generate docs: `php artisan l5-swagger:generate`. Viewable at `/api/documentation`.

This is a massive "wow" — the client gets interactive API docs they can show clinic integrators.

---

# Part 5 — Overdelivery Features (The "Wow")

## 12. Patient Timeline & Visit History

### What It Is

A chronological feed of everything that's happened to a patient: registrations, consultations, prescriptions, invoices. Displayed on the patient show page as a vertical timeline.

### Implementation

Add a method to `PatientService`:

```php
public function getTimeline(Patient $patient): Collection
{
    $events = collect();

    // Registration
    $events->push([
        'type'       => 'registration',
        'date'       => $patient->created_at,
        'title'      => 'Patient Registered',
        'subtitle'   => "Registered by {$patient->registeredBy->name}",
        'icon'       => 'phosphor-user-plus',
    ]);

    // Consultations
    foreach ($patient->consultations()->with('doctor')->latest()->get() as $c) {
        $events->push([
            'type'       => 'consultation',
            'date'       => $c->created_at,
            'title'      => "Consultation — {$c->diagnosis ?? 'In Progress'}",
            'subtitle'   => "Dr. {$c->doctor->name} · {$c->status->label()}",
            'icon'       => 'phosphor-stethoscope',
            'link'       => route('consultations.show', $c),
        ]);
    }

    // Invoices
    foreach ($patient->invoices()->latest()->get() as $inv) {
        $events->push([
            'type'       => 'invoice',
            'date'       => $inv->created_at,
            'title'      => "Invoice {$inv->invoice_number}",
            'subtitle'   => "₦" . number_format($inv->total, 2) . " · {$inv->status->label()}",
            'icon'       => 'phosphor-receipt',
            'link'       => route('invoices.show', $inv),
        ]);
    }

    return $events->sortByDesc('date')->values();
}
```

### UI Component

Create a `<x-timeline>` Blade component that renders each event as a vertical line with date markers, icons, and links.

---

## 13. Audit Trail System

### What It Is

Every write operation (create, update, delete) on clinical data is logged with who, what, when, and the before/after values. Admin can view the full log.

### Implementation

**Model:** `app/Models/AuditLog.php`

```php
class AuditLog extends Model
{
    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'user_id', 'user_name', 'action', 'model_type', 'model_id',
        'description', 'old_values', 'new_values', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function record(string $action, Model $model, ?string $description = null): void
    {
        $user = auth()->user();
        $className = class_basename($model);

        static::create([
            'user_id'    => $user?->id,
            'user_name'  => $user?->name ?? 'System',
            'action'     => $action,
            'model_type' => get_class($model),
            'model_id'   => $model->getKey(),
            'description' => $description ?? "{$action} {$className} #{$model->getKey()}",
            'old_values'  => $action === 'updated' ? $model->getOriginal() : null,
            'new_values'  => $action !== 'deleted' ? $model->getAttributes() : null,
            'ip_address'  => request()?->ip(),
            'created_at'  => now(),
        ]);
    }
}
```

**Trait:** `app/Traits/HasAuditTrail.php` — apply to Patient, Consultation, Prescription, Invoice.

**Route:** `GET /audit-log` (Admin only) — searchable, filterable by model type and user.

---

## 14. PDF Invoice Generation

### Package

```bash
composer require barryvdh/laravel-dompdf
```

### Implementation

Create a Blade view specifically for the PDF layout (`resources/views/invoices/pdf.blade.php`), then:

```php
// InvoiceController
public function downloadPdf(Invoice $invoice)
{
    $invoice->load(['patient', 'items', 'createdBy']);

    $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

    return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
}
```

The PDF should look like a real clinic invoice — AfriChart logo, patient details, itemized table, totals, payment status.

---

## 15. Dashboard Analytics with Charts

### Library

Use **Chart.js** via a CDN or npm. Render with Alpine.js + a `<canvas>` element.

### Admin Dashboard Charts

**Patient Registrations (30 Days) — Line Chart**

```php
// DashboardService
public function getRegistrationTrend(int $days = 30): array
{
    return Patient::selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->where('created_at', '>=', now()->subDays($days))
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date')
        ->toArray();
}
```

**Revenue by Month (6 Months) — Bar Chart**

```php
public function getRevenueTrend(int $months = 6): array
{
    return Invoice::where('status', 'paid')
        ->where('paid_at', '>=', now()->subMonths($months))
        ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(total) as revenue")
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('revenue', 'month')
        ->toArray();
}
```

**Consultation Status Distribution — Donut Chart**

```php
public function getConsultationStatusBreakdown(): array
{
    return Consultation::selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
}
```

### Chart Blade Component

```php
// resources/views/components/chart.blade.php
@props(['id', 'type' => 'line', 'labels', 'data', 'label' => ''])

<div class="bg-page border border-line rounded-card p-6">
    <canvas id="{{ $id }}" height="200"></canvas>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.getElementById('{{ $id }}'), {
        type: '{{ $type }}',
        data: {
            labels: @json($labels),
            datasets: [{
                label: '{{ $label }}',
                data: @json($data),
                borderColor: '#1a1a1a',
                backgroundColor: 'rgba(26, 26, 26, 0.1)',
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#ececec' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
```

---

## 16. Data Export (CSV)

Admin can export patient lists, consultation logs, and invoice reports as CSV files.

```php
// In PatientController or a dedicated ExportController
public function exportCsv(Request $request)
{
    $this->authorize('export', Patient::class); // Admin only

    $patients = Patient::with('registeredBy')->latest()->get();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="patients-' . now()->format('Y-m-d') . '.csv"',
    ];

    $callback = function () use ($patients) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['Patient ID', 'Full Name', 'DOB', 'Age', 'Phone', 'Blood Group', 'Allergies', 'Registered By', 'Date']);

        foreach ($patients as $p) {
            fputcsv($file, [
                $p->patient_id, $p->full_name, $p->date_of_birth->format('Y-m-d'),
                $p->age, $p->phone, $p->blood_group->value,
                $p->allergies ?? '', $p->registeredBy?->name ?? '',
                $p->created_at->format('Y-m-d H:i'),
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
```

---

## 17. Print-Friendly Views

Add a `@media print` CSS block and a "Print" button to key pages:

**Pages that need print views:**
- Patient detail page (patient card)
- Consultation detail page (clinical notes)
- Invoice page (the invoice itself)
- Prescription list (for a consultation)

```css
/* In app.css */
@media print {
    /* Hide sidebar, topbar, buttons, navigation */
    aside, header, nav, .no-print, button, a[href] { display: none !important; }
    /* Full width content */
    main { padding: 0 !important; margin: 0 !important; }
    /* Clean borders */
    .print-clean { border: 1px solid #ddd !important; }
}
```

Print button:

```html
<button onclick="window.print()" class="no-print inline-flex items-center gap-1.5 ...">
    <x-phosphor-printer class="w-4 h-4" />
    Print
</button>
```

---

## 18. Patient Queue / Waiting List

### The Concept

When a patient arrives at the clinic, the receptionist checks them in. They get a queue number for the day. The doctor sees their queue and calls patients in order.

### QueueService

```php
class PatientQueueService extends BaseService
{
    public function checkIn(int $patientId, int $checkedInBy, ?int $doctorId = null, ?string $reason = null): QueueEntry
    {
        $queueNumber = $this->getNextQueueNumber();

        return $this->queueRepository->create([
            'patient_id'         => $patientId,
            'checked_in_by'      => $checkedInBy,
            'assigned_doctor_id' => $doctorId,
            'status'             => QueueStatus::Waiting,
            'queue_number'       => $queueNumber,
            'reason'             => $reason,
            'checked_in_at'      => now(),
        ]);
    }

    private function getNextQueueNumber(): int
    {
        $lastToday = $this->queueRepository->getLastTodayQueueNumber();
        return $lastToday + 1;
    }

    public function markInConsultation(int $patientId): void
    {
        $entry = $this->queueRepository->findTodayByPatient($patientId);
        $entry?->update([
            'status'  => QueueStatus::InConsultation,
            'seen_at' => now(),
        ]);
    }

    public function markCompleted(int $patientId): void
    {
        $entry = $this->queueRepository->findTodayByPatient($patientId);
        $entry?->update([
            'status'       => QueueStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
```

### Queue UI

The queue page is a real-time-feeling dashboard (refreshes via meta tag or Alpine polling):

```
┌──────────────────────────────────────────────┐
│  Today's Queue · 10 June 2026                │
│  ────────────────────────────────────────────│
│                                              │
│  #1 │ Chioma Nwosu   │ Dr. Emeka │ ✅ Done  │
│  #2 │ Emeka Obi      │ Dr. Emeka │ 🔵 In    │
│  #3 │ Fatima Mohammed │ —         │ ⏳ Wait  │
│  #4 │ David Adeyemi  │ —         │ ⏳ Wait  │
│                                              │
│  [+ Check In Patient]                        │
└──────────────────────────────────────────────┘
```

---

# Part 6 — Execution

## 19. Milestone Delivery Map

Map deliverables to payment milestones so you know exactly what to build and when to invoice.

### Milestone 1 — Agreement + Kickoff (₦150,000)

**Trigger:** Agreement signed (already done)

**Deliverables by this point:**
- Phase 0 approved ✅
- Role expansion (4 roles with dashboards)
- Patient Queue module
- Database migrations for all new tables
- All new models, repositories, services scaffolded

### Milestone 2 — Working Demo (₦250,000)

**Trigger:** Client approves working demo

**Deliverables:**
- Consultation & Clinical Notes module (full CRUD + vitals)
- Prescription Recording module
- Billing & Invoice Generation module
- Patient Timeline
- Audit Trail
- Enhanced Admin Dashboard with charts
- All web UI complete and polished

**This is the big milestone.** The client should be able to walk through the entire clinical workflow end-to-end: check in patient → record vitals → start consultation → write notes → add prescriptions → generate invoice → mark paid.

### Milestone 3 — Final Delivery + Deployment (₦100,000)

**Trigger:** Code delivered + deployed + repository handover

**Deliverables:**
- REST API (all endpoints, Sanctum auth, Swagger docs)
- PDF invoice generation
- CSV data export
- Print-friendly views
- Production deployment
- Updated README + WALKTHROUGH
- Full handover package

---

## 20. Day-by-Day Build Plan

### Week 1: Foundation + Queue + Roles (Days 1–7)

| Day | Focus |
|---|---|
| 1 | New migrations (all 6 tables), new enums, update UserRole. Run migrations. |
| 2 | New models (Consultation, Prescription, Invoice, InvoiceItem, QueueEntry, AuditLog) with relationships + casts. HasAuditTrail trait. |
| 3 | New repositories (Consultation, Prescription, Invoice, Queue, AuditLog). |
| 4 | New services (Consultation, Prescription, Invoice, Queue). Business logic: ID generation, totals calculation, queue numbering. |
| 5 | Form Requests for all new modules. Policies for Consultation + Invoice. |
| 6 | Expand auth: 4 roles, new seeders, update registration form with 4 tabs, update invite codes. New role dashboards (Nurse + Receptionist). |
| 7 | Queue module: controller, views (check-in form, queue display), wire into receptionist + nurse dashboards. Test full queue flow. |

### Week 2: Core Modules + UI (Days 8–14)

| Day | Focus |
|---|---|
| 8 | Consultation controller + routes. Create, show, edit views. |
| 9 | Consultation show page (the clinical workspace). Vitals form (nurse/doctor). |
| 10 | Prescription module: controller, nested routes, dynamic Alpine form, medication presets JSON. |
| 11 | Invoice module: controller, routes, generate from consultation, item editing, mark paid. |
| 12 | Invoice UI: the invoice page, inline item price editing, status badges. PDF generation (dompdf). |
| 13 | Patient timeline on patient show page. Enhanced patient show with consultations/invoices tabs. |
| 14 | Audit trail: trait on all models, admin log view with search/filter. |

### Week 3: Dashboard, API, Polish (Days 15–21)

| Day | Focus |
|---|---|
| 15 | Enhanced Admin dashboard: 6 stat cards, Chart.js charts (registrations, revenue, consultations). Activity feed. |
| 16 | Doctor/Nurse/Receptionist dashboards fully wired with real data. |
| 17 | REST API: routes, auth controller, patient + consultation API controllers. |
| 18 | REST API: prescription + invoice + queue + dashboard API controllers. API Resources for all models. |
| 19 | Swagger annotations + generate docs. Test all API endpoints manually. |
| 20 | CSV export (patients, consultations, invoices). Print CSS. Data seeding for demo (consultations, prescriptions, invoices). |
| 21 | Full testing checklist. Bug fixes. UI polish. |

### Days 22–25: Deployment + Handover

| Day | Focus |
|---|---|
| 22 | Deploy to production (africhart.mgbah.dev). Full database with demo data. |
| 23 | Update README, WALKTHROUGH docs for Phase 1. |
| 24 | Client demo walkthrough. Fix any issues raised. |
| 25 | Final commit. Repository handover. Milestone 3 invoice. |

---

## 21. Testing Checklist

### Role System
- [ ] All 4 roles can log in
- [ ] Each role sees correct dashboard
- [ ] Registration works with all 4 invite codes
- [ ] Role middleware blocks unauthorized access
- [ ] Policies prevent cross-role actions (doctor can't access invoices, receptionist can't access consultations)

### Patient Queue
- [ ] Receptionist can check in a patient
- [ ] Queue number auto-increments daily
- [ ] Can assign a doctor to a queued patient
- [ ] Queue status updates when consultation starts/completes
- [ ] Doctor sees only their assigned queue
- [ ] Cancelled queue entries show correctly

### Consultations
- [ ] Doctor can start consultation (from queue or patient page)
- [ ] Consultation ID auto-generates correctly
- [ ] Vitals form works (nurse and doctor)
- [ ] BMI auto-calculates from weight + height
- [ ] Clinical notes save correctly
- [ ] Diagnosis and plan fields are optional during creation, editable later
- [ ] Only the creating doctor (or admin) can edit a consultation
- [ ] "Complete Consultation" changes status and updates queue
- [ ] Consultation show page displays all sections (patient info, vitals, notes, prescriptions, invoice)

### Prescriptions
- [ ] Can add prescription from consultation page
- [ ] Medication autocomplete works from preset list
- [ ] Custom medications can be typed freely
- [ ] Multiple prescriptions can be added
- [ ] Prescriptions display on consultation show page
- [ ] Can delete a prescription (doctor only)

### Invoices
- [ ] "Generate Invoice" creates from consultation
- [ ] Auto-adds consultation fee + prescription items
- [ ] Receptionist can edit item prices
- [ ] Totals recalculate on item edit
- [ ] Can add/remove custom line items
- [ ] Mark as Paid works with payment method selection
- [ ] Invoice status badges display correctly
- [ ] PDF download generates correct invoice
- [ ] Invoice list page with status filter

### Patient Timeline
- [ ] Timeline shows on patient detail page
- [ ] Registration, consultations, and invoices appear chronologically
- [ ] Links to consultation/invoice detail pages work

### Audit Trail
- [ ] Patient create/update logged
- [ ] Consultation create/update logged
- [ ] Prescription create logged
- [ ] Invoice create/update logged
- [ ] Admin can view audit log
- [ ] Audit log is searchable and filterable

### Dashboard Analytics
- [ ] Admin sees 6 stat cards with correct numbers
- [ ] Patient registration chart renders with correct data
- [ ] Revenue chart renders with correct data
- [ ] Consultation status chart renders
- [ ] Activity feed shows recent audit entries

### REST API
- [ ] Login returns token
- [ ] Token auth works on all endpoints
- [ ] Unauthenticated requests return 401
- [ ] Unauthorized role access returns 403
- [ ] All CRUD endpoints return correct JSON structure
- [ ] Pagination works on list endpoints
- [ ] Search/filter works on patient list
- [ ] Swagger docs load at /api/documentation

### Data Export
- [ ] CSV export downloads for patients
- [ ] CSV export downloads for consultations
- [ ] CSV export downloads for invoices
- [ ] Only admin can access exports

### Print
- [ ] Patient detail page prints cleanly (no sidebar/nav)
- [ ] Consultation page prints cleanly
- [ ] Invoice page prints as a proper invoice

### General
- [ ] No `dd()` or `dump()` in code
- [ ] No commented-out code blocks
- [ ] All new code follows repository + service pattern
- [ ] All models have proper fillable/casts
- [ ] All new tables have proper indexes
- [ ] Mobile responsive layout works on all new pages
- [ ] Toast notifications appear on all new actions

---

## 22. Deployment & Handover

### Production Deployment

Same process as Phase 0 (cPanel at africhart.mgbah.dev), plus:

```bash
# New dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# New migrations
php artisan migrate

# Seed demo data for consultations/prescriptions/invoices
php artisan db:seed --class=Phase1DemoSeeder

# Generate Swagger docs
php artisan l5-swagger:generate

# Cache (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Updated README

Add demo credentials for all 4 roles, Phase 1 features list, API documentation link, and updated architecture section.

### Handover Package

| Item | Format |
|---|---|
| Source code | GitHub repo (private, client added as collaborator) |
| Database schema | Updated SQL dump in `database/schema/` |
| API documentation | Interactive Swagger at `/api/documentation` |
| Project walkthrough | Updated `docs/WALKTHROUGH.md` |
| Deployment guide | In README |
| Demo environment | `https://africhart.mgbah.dev` with full demo data |

---

**Phase 1 scope is ₦500,000 of contracted work. You're shipping ₦800,000 of value. The timeline, audit trail, queue system, PDF invoices, charts, CSV exports, and print views aren't in the contract — they're your reputation. Build clean. Ship on time. Overdeliver quietly.**
