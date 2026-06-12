<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Admins and receptionists manage billing; doctors may view.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isReceptionist() || $user->isDoctor();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->isReceptionist() || $user->isDoctor();
    }

    /**
     * Only admins and receptionists create/manage invoices.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }
}
