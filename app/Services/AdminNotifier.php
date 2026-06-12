<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AdminActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Sends activity notifications to all admin users. Mail failures are swallowed
 * (logged) so they never break the action that triggered them.
 */
class AdminNotifier
{
    public function patientRegistered(Patient $patient, User $actor): void
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
        ), excludeUserId: $actor->id);
    }

    public function patientUpdated(Patient $patient, User $actor): void
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
        ), excludeUserId: $actor->id);
    }

    public function consultationCompleted(Consultation $consultation, User $actor): void
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
        ), excludeUserId: $actor->id);
    }

    public function invoiceIssued(Invoice $invoice, User $actor): void
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
        ), excludeUserId: $actor->id);
    }

    public function staffRegistered(User $user): void
    {
        $this->send(new AdminActivity(
            subject: 'New staff account registered',
            heading: 'A new staff account was created',
            lines: [
                "Name: {$user->name}",
                "Email: {$user->email}",
                'Role: '.$user->role->label(),
            ],
        ), excludeUserId: $user->id);
    }

    public function emailVerified(User $user): void
    {
        $this->send(new AdminActivity(
            subject: 'Staff email verified',
            heading: 'A staff member verified their email',
            lines: [
                "Name: {$user->name}",
                "Email: {$user->email}",
                'Role: '.$user->role->label(),
            ],
        ), excludeUserId: $user->id);
    }

    /**
     * Send to every admin (optionally excluding one user, e.g. the actor).
     */
    private function send(AdminActivity $notification, ?int $excludeUserId = null): void
    {
        try {
            $admins = User::where('role', 'admin')
                ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, $notification);
            }
        } catch (\Throwable $e) {
            Log::warning('Admin notification failed: '.$e->getMessage());
        }
    }
}
