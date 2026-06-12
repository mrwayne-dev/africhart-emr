<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\QueueStatus;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\ConsultationService;
use App\Services\InvoiceService;
use App\Services\PatientQueueService;
use App\Services\PrescriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic slice of clinical activity — consultations, vitals,
 * prescriptions, invoices and a live queue — so the demo environment looks
 * lived-in (and the dashboard charts have data). Everything goes through the
 * services, so IDs/totals match what the app would produce.
 */
class Phase1DemoSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = User::where('role', 'doctor')->first();
        $receptionist = User::where('role', 'receptionist')->first();

        if (! $doctor || ! $receptionist) {
            $this->command?->warn('Phase1DemoSeeder skipped — run UserSeeder first.');

            return;
        }

        $consultationService = app(ConsultationService::class);
        $invoiceService = app(InvoiceService::class);
        $queueService = app(PatientQueueService::class);

        $diagnoses = [
            ['Malaria', 'Fever and chills for 3 days', ['Artemether/Lumefantrine', '20/120mg', '2 times daily for 3 days', '3 days', 6]],
            ['Tension headache', 'Persistent headache for 2 days', ['Paracetamol', '500mg', '3 times daily', '5 days', 15]],
            ['Hypertension', 'Routine BP review', ['Amlodipine', '5mg', 'Once daily', '30 days', 30]],
            ['Peptic ulcer disease', 'Epigastric pain after meals', ['Omeprazole', '20mg', 'Once daily', '14 days', 14]],
            ['Upper respiratory infection', 'Sore throat and cough', ['Amoxicillin', '500mg', '3 times daily', '7 days', 21]],
            ['Type 2 diabetes', 'Follow-up, fasting glucose elevated', ['Metformin', '500mg', '2 times daily', '30 days', 60]],
        ];

        $patients = Patient::orderBy('id')->take(12)->get();

        foreach ($patients as $index => $patient) {
            [$diagnosis, $complaint, $rx] = $diagnoses[$index % count($diagnoses)];

            $daysAgo = ($index * 3) % 40; // spread across the last ~6 weeks

            $consultation = $consultationService->startConsultation([
                'patient_id' => $patient->id,
                'chief_complaint' => $complaint,
                'clinical_notes' => 'Patient examined. '.$diagnosis.' suspected; managed accordingly.',
                'diagnosis' => $diagnosis,
                'plan' => 'Prescribe medication, advise rest and review if symptoms persist.',
            ], $doctor->id);

            $consultationService->recordVitals($consultation, [
                'temperature' => 36.5 + ($index % 5) * 0.4,
                'blood_pressure' => (118 + $index % 10).'/'.(78 + $index % 6),
                'pulse_rate' => 68 + ($index % 12),
                'weight' => 60 + ($index % 25),
                'height' => 160 + ($index % 20),
            ]);

            // Prescription
            [$med, $dose, $freq, $dur, $qty] = $rx;
            app(PrescriptionService::class)->addToConsultation($consultation, [
                'medication_name' => $med,
                'dosage' => $dose,
                'frequency' => $freq,
                'duration' => $dur,
                'route' => 'oral',
                'quantity' => $qty,
            ], $doctor->id);

            $consultationService->completeConsultation($consultation);

            // Backdate the consultation for chart spread.
            $created = now()->subDays($daysAgo);
            $consultation->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();

            $this->logActivity('updated', $consultation, "Completed consultation {$consultation->consultation_id} for {$patient->full_name}", $doctor, $created);

            // Leave ~1-in-5 completed consultations un-invoiced so the reception
            // "Ready to Invoice" worklist has something to act on in the demo.
            if ($index % 5 === 0) {
                continue;
            }

            // Invoice — price the medication line, then maybe mark paid.
            $invoice = $invoiceService->generateFromConsultation($consultation, $receptionist->id);
            $medItem = $invoice->items()->where('category', 'medication')->first();
            if ($medItem) {
                $invoiceService->updateItem($medItem, [
                    'description' => $medItem->description,
                    'unit_price' => 200 + ($index % 5) * 150,
                    'quantity' => $medItem->quantity,
                ]);
            }

            $invoice->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();

            if ($index % 3 !== 0) { // ~two-thirds paid
                $method = [PaymentMethod::Cash, PaymentMethod::Transfer, PaymentMethod::Card][$index % 3];
                $invoiceService->markAsPaid($invoice, $method->value);
                $invoice->forceFill(['paid_at' => $created->copy()->addHours(1)])->saveQuietly();
                $this->logActivity('updated', $invoice, "Marked invoice {$invoice->invoice_number} as paid", $receptionist, $created->copy()->addHours(1));
            }
        }

        // A live queue for today: a couple waiting, one in consultation.
        $today = Patient::orderByDesc('id')->take(4)->get();
        foreach ($today as $i => $patient) {
            $entry = $queueService->checkIn(
                patientId: $patient->id,
                checkedInBy: $receptionist->id,
                doctorId: $i % 2 === 0 ? $doctor->id : null,
                reason: ['Fever', 'Follow-up', 'New complaint', 'Lab results'][$i],
            );

            if ($i === 0) {
                $entry->update(['status' => QueueStatus::InConsultation, 'seen_at' => now()]);
            }
        }

        $this->command?->info('Phase1DemoSeeder: seeded '.$patients->count().' consultations, prescriptions, invoices and a live queue.');
    }

    /**
     * Write a backdated audit entry attributed to a real staff member so the
     * admin activity feed has content on a fresh demo (model events are muted
     * during seeding, so we record these explicitly).
     */
    private function logActivity(string $action, Model $model, string $description, User $user, \DateTimeInterface $at): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'description' => $description,
            'ip_address' => null,
            'created_at' => $at,
        ]);
    }
}
