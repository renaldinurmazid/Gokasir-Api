<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUsageLog extends Model
{
    protected $table = 'token_usage_logs';
    
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'store_id', 'user_id', 'type',
        'amount', 'balance_before', 'balance_after',
        'reference_type', 'reference_id', 'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
