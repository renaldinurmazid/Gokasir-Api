<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'store_id', 'table_id', 'session_token',
        'pax', 'customer_name', 'customer_phone', 'status',
        'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function orders()
    {
        return $this->hasMany(TableOrder::class, 'session_id');
    }
}
