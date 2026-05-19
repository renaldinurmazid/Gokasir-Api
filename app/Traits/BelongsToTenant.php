<?php

namespace App\Traits;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function scopeForTenant($query, $tenantId = null)
    {
        return $query->where('tenant_id', $tenantId ?? auth()->user()->tenant_id);
    }
}
