<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Staff;
use App\Notifications\AdminActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Sends activity notifications to all admin users. Mail failures are swallowed
 * (logged) so they never break the action that triggered them.
 */
class AdminNotifier
{
    public function patientRegistered(Patient $patient, Staff $actor): void
    {
        $this->send(new AdminActivity(
            subject: 'New patient registered — '.$patient->patient_id,
            heading: 'A new patient was registered',
            lines: [
                "Patient: {$patient->full_name} ({$patient->patient_id})",
                "Registered by: {$actor->name}",
            ],
            actionText: 'View patient',
            actionUrl: route('patients.show', $patient),
        ), excludeStaffId: $actor->id);
    }

    public function patientUpdated(Patient $patient, Staff $actor): void
    {
        $this->send(new AdminActivity(
            subject: 'Patient record updated — '.$patient->patient_id,
            heading: 'A patient record was updated',
            lines: [
                "Patient: {$patient->full_name} ({$patient->patient_id})",
                "Updated by: {$actor->name}",
            ],
            actionText: 'View patient',
            actionUrl: route('patients.show', $patient),
        ), excludeStaffId: $actor->id);
    }

    public function consultationCompleted(Consultation $consultation, Staff $actor): void
    {
        $this->send(new AdminActivity(
            subject: 'Consultation completed — '.$consultation->consultation_id,
            heading: 'A consultation was completed',
            lines: [
                "Patient: {$consultation->patient?->full_name} ({$consultation->patient?->patient_id})",
                "Doctor: {$actor->name}",
                'Diagnosis: '.($consultation->diagnosis ?: '—'),
            ],
            actionText: 'View consultation',
            actionUrl: route('consultations.show', $consultation),
        ), excludeStaffId: $actor->id);
    }

    public function invoiceIssued(Invoice $invoice, Staff $actor): void
    {
        $this->send(new AdminActivity(
            subject: 'Invoice issued — '.$invoice->invoice_number,
            heading: 'An invoice was issued',
            lines: [
                "Patient: {$invoice->patient?->full_name} ({$invoice->patient?->patient_id})",
                'Total: ₦'.number_format((float) $invoice->total, 2),
                "Issued by: {$actor->name}",
            ],
            actionText: 'View invoice',
            actionUrl: route('invoices.show', $invoice),
        ), excludeStaffId: $actor->id);
    }

    public function staffRegistered(Staff $user): void
    {
        $this->send(new AdminActivity(
            subject: 'New staff account registered',
            heading: 'A new staff account was created',
            lines: [
                "Name: {$user->name}",
                "Email: {$user->email}",
                'Role: '.$user->role->label(),
            ],
        ), excludeStaffId: $user->id);
    }

    public function emailVerified(Staff $user): void
    {
        $this->send(new AdminActivity(
            subject: 'Staff email verified',
            heading: 'A staff member verified their email',
            lines: [
                "Name: {$user->name}",
                "Email: {$user->email}",
                'Role: '.$user->role->label(),
            ],
        ), excludeStaffId: $user->id);
    }

    /**
     * Send to every admin (optionally excluding one user, e.g. the actor).
     */
    private function send(AdminActivity $notification, ?int $excludeStaffId = null): void
    {
        /*
         * Stamped here, in the one funnel every caller passes through, rather
         * than at the six construction sites above. Six places to remember is
         * five chances to forget, and forgetting is invisible: the mail still
         * sends, just without saying which clinic it came from.
         *
         * Read now, while we are certainly in the clinic's context — not in the
         * queued toMail(), which runs in a worker.
         */
        $notification->clinicName ??= tenant('name');

        try {
            $admins = Staff::where('role', 'admin')
                ->when($excludeStaffId, fn ($q) => $q->where('id', '!=', $excludeStaffId))
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, $notification);
            }
        } catch (\Throwable $e) {
            Log::warning('Admin notification failed: '.$e->getMessage());
        }
    }
}
