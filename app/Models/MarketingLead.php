<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'clinic_name', 'contact_name', 'email', 'phone', 'city', 'doctors', 'message'])]
class MarketingLead extends Model
{
    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'doctors' => 'integer',
        ];
    }
}
