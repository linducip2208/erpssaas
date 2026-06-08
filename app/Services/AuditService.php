<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(
        string $action,
        $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditLog {
        return AuditLog::create([
            'action' => $action,
            'model_type' => $model instanceof \Illuminate\Database\Eloquent\Model
                ? get_class($model)
                : $model,
            'model_id' => $model instanceof \Illuminate\Database\Eloquent\Model
                ? $model->id
                : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => Auth::id(),
            'tenant_id' => Auth::check() ? creatorId() : null,
        ]);
    }
}
