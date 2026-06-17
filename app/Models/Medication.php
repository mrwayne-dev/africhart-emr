<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'default_price',
    'dosages',
    'routes',
    'common_frequency',
    'is_active',
])]
class Medication extends Model
{
    use HasAuditTrail;

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'dosages' => 'array',
            'routes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Audit ---

    public function auditDescription(string $action): string
    {
        return match ($action) {
            'created' => "Added medication {$this->name} to the catalog",
            'updated' => "Updated medication {$this->name}",
            'deleted' => "Removed medication {$this->name} from the catalog",
            default => "{$action} medication {$this->name}",
        };
    }
}
