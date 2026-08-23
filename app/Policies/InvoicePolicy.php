<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\Staff;

class InvoicePolicy
{
    /**
     * Admins and receptionists manage billing; doctors may view.
     */
    public function viewAny(Staff $user): bool
    {
        return $user->isAdmin() || $user->isReceptionist() || $user->isDoctor();
    }

    public function view(Staff $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->isReceptionist() || $user->isDoctor();
    }

    /**
     * Only admins and receptionists create/manage invoices.
     */
    public function create(Staff $user): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }

    public function update(Staff $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }

    public function markPaid(Staff $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }
}
