<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceRepository extends BaseRepository
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginated invoice list with optional search and status filter.
     */
    public function getPaginated(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->with(['patient', 'createdBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($p) use ($search) {
                        $p->where('full_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    public function countByInvoiceNumberPrefix(string $prefix): int
    {
        return $this->model->where('invoice_number', 'like', $prefix.'%')->count();
    }

    /**
     * Sum of paid invoice totals since the given date.
     */
    public function revenueSince(\DateTimeInterface $since): float
    {
        return (float) $this->model
            ->where('status', 'paid')
            ->where('paid_at', '>=', $since)
            ->sum('total');
    }

    public function paymentsReceivedToday(): float
    {
        return (float) $this->model
            ->where('status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('total');
    }
}
