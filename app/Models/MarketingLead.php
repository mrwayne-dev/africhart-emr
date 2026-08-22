<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'plan', 'clinic_name', 'contact_name', 'email', 'phone', 'city', 'doctors', 'preferred_time', 'heard_from', 'message'])]
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
