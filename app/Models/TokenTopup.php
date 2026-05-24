<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenTopup extends Model
{
    protected $table = 'token_topups';

    protected $fillable = [
        'tenant_id', 'user_id', 'pricing_id',
        'order_number', 'token_amount', 'price', 'qty',
        'ipaymu_trx_id', 'ipaymu_reference',
        'payment_method', 'payment_channel', 'payment_url',
        'ipaymu_raw_response', 'status',
        'paid_at', 'expired_at',
        'balance_before', 'balance_after',
    ];

    protected $casts = [
        'paid_at'    => 'datetime',
        'expired_at' => 'datetime',
        'price'      => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pricing()
    {
        return $this->belongsTo(TokenPricing::class, 'pricing_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
