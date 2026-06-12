<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QueueStatus;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Patient;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsultationRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\PatientQueueRepository;
use App\Repositories\PatientRepository;
use Illuminate\Database\Eloquent\Collection;

class DashboardService extends BaseService
{
    public function __construct(
        protected PatientRepository $patientRepository,
        protected ConsultationRepository $consultationRepository,
        protected InvoiceRepository $invoiceRepository,
        protected PatientQueueRepository $queueRepository,
        protected AuditLogRepository $auditLogRepository,
    ) {
        parent::__construct($patientRepository);
    }

    /**
     * Admin dashboard statistics (6 cards).
     */
    public function getAdminStats(): array
    {
        return [
            'total_patients' => $this->patientRepository->count(),
            'today_registered' => $this->patientRepository->countToday(),
            'this_week' => $this->patientRepository->countThisWeek(),
            'today_consultations' => $this->consultationRepository->countToday(),
            'pending_invoices' => $this->invoiceRepository->countByStatus(InvoiceStatus::Draft->value)
                + $this->invoiceRepository->countByStatus(InvoiceStatus::Issued->value),
            'revenue_this_month' => $this->invoiceRepository->revenueSince(now()->startOfMonth()),
        ];
    }

    public function getDoctorStats(int $doctorId): array
    {
        return [
            'consultations_today' => $this->consultationRepository->countToday($doctorId),
            'consultations_week' => $this->consultationRepository->countThisWeek($doctorId),
            'patients_seen_total' => $this->consultationRepository->countForDoctor($doctorId),
        ];
    }

    public function getNurseStats(): array
    {
        return [
            'waiting' => $this->queueRepository->countTodayByStatus(QueueStatus::Waiting->value),
            'in_consultation' => $this->queueRepository->countTodayByStatus(QueueStatus::InConsultation->value),
            'completed_today' => $this->queueRepository->countTodayByStatus(QueueStatus::Completed->value),
        ];
    }

    public function getReceptionistStats(): array
    {
        return [
            'checked_in_today' => $this->queueRepository->countTodayCheckedIn(),
            'pending_invoices' => $this->invoiceRepository->countByStatus(InvoiceStatus::Draft->value)
                + $this->invoiceRepository->countByStatus(InvoiceStatus::Issued->value),
            'payments_today' => $this->invoiceRepository->paymentsReceivedToday(),
        ];
    }

    public function getRecentPatients(int $limit = 10)
    {
        return $this->patientRepository->getRecent($limit);
    }

    /**
     * Completed consultations awaiting an invoice — the reception billing worklist.
     */
    public function getReadyToInvoice(int $limit = 20): Collection
    {
        return $this->consultationRepository->completedWithoutInvoice($limit);
    }

    public function getRecentConsultations(int $limit = 10, ?int $doctorId = null)
    {
        return $this->consultationRepository->getRecent($limit, $doctorId);
    }

    /**
     * Recent audit entries for the admin activity feed.
     */
    public function getActivityFeed(int $limit = 20): Collection
    {
        return $this->auditLogRepository->getRecent($limit);
    }

    // --- Chart data ---

    /**
     * Patient registrations per day over the last N days (date => count),
     * with zero-filled gaps so the line chart is continuous.
     *
     * @return array<string, int>
     */
    public function getRegistrationTrend(int $days = 30): array
    {
        $counts = Patient::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $series[now()->subDays($i)->format('j M')] = (int) ($counts[$day] ?? 0);
        }

        return $series;
    }

    /**
     * Paid revenue per month over the last N months (YYYY-MM => total).
     *
     * @return array<string, float>
     */
    public function getRevenueTrend(int $months = 6): array
    {
        $totals = Invoice::where('status', InvoiceStatus::Paid->value)
            ->where('paid_at', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(total) as revenue")
            ->groupBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $series[$month->format('M Y')] = (float) ($totals[$month->format('Y-m')] ?? 0);
        }

        return $series;
    }

    /**
     * Consultation counts by status (label => count) for the donut chart.
     *
     * @return array<string, int>
     */
    public function getConsultationStatusBreakdown(): array
    {
        $raw = Consultation::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $series = [];
        foreach (ConsultationStatus::cases() as $case) {
            $series[$case->label()] = (int) ($raw[$case->value] ?? 0);
        }

        return $series;
    }
}
