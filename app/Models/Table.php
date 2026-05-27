<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Table extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'store_id', 'name', 'code',
        'capacity', 'location', 'is_active', 'qr_url', 'qr_image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sessions()
    {
        return $this->hasMany(TableSession::class);
    }

    public function orders()
    {
        return $this->hasMany(TableOrder::class);
    }

    // Sesi aktif saat ini di meja ini (status: active atau ordered)
    public function activeSession()
    {
        return $this->hasOne(TableSession::class)
            ->whereIn('status', ['active', 'ordered'])
            ->latest('opened_at');
    }

    // URL halaman order customer
    public function getOrderUrl(): string
    {
        return url('order/' . $this->code);
    }
}
