<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'tenant_id',
        'tenant_name',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public static function record(string $action, ?Tenant $tenant = null, array $meta = []): self
    {
        return static::create([
            'admin_id'    => auth('admin')->id(),
            'action'      => $action,
            'tenant_id'   => $tenant?->id,
            'tenant_name' => $tenant?->business_name,
            'meta'        => $meta ?: null,
            'ip_address'  => request()->ip(),
        ]);
    }
}
