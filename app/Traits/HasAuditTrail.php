<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Auto-logs create/update/delete operations to the audit_logs table.
 *
 * Apply to any model that needs tracking. The optional auditDescription()
 * method on the model lets it provide a human-readable description.
 */
trait HasAuditTrail
{
    protected static function bootHasAuditTrail(): void
    {
        static::created(fn (Model $model) => AuditLog::record('created', $model, static::auditDescriptionFor('created', $model)));
        static::updated(fn (Model $model) => AuditLog::record('updated', $model, static::auditDescriptionFor('updated', $model)));
        static::deleted(fn (Model $model) => AuditLog::record('deleted', $model, static::auditDescriptionFor('deleted', $model)));
    }

    /**
     * Resolve a human-readable description if the model defines one.
     */
    protected static function auditDescriptionFor(string $action, Model $model): ?string
    {
        return method_exists($model, 'auditDescription')
            ? $model->auditDescription($action)
            : null;
    }
}
