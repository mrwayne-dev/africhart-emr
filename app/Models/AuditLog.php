<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_name',
    'action',
    'model_type',
    'model_id',
    'description',
    'old_values',
    'new_values',
    'ip_address',
    'created_at',
])]
class AuditLog extends Model
{
    public $timestamps = false; // We only track created_at

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- Behaviour ---

    /**
     * Record an audit entry for a model write operation.
     */
    public static function record(string $action, Model $model, ?string $description = null): void
    {
        $user = auth()->user();
        $className = class_basename($model);

        static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'description' => $description ?? "{$action} {$className} #{$model->getKey()}",
            'old_values' => $action === 'updated' ? $model->getOriginal() : null,
            'new_values' => $action !== 'deleted' ? $model->getAttributes() : null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    // --- Accessors ---

    public function getModelLabelAttribute(): string
    {
        return class_basename($this->model_type);
    }
}
