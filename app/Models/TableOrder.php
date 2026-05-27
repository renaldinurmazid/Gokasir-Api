<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class TableOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'store_id', 'table_id', 'session_id',
        'order_number', 'status',
        'payment_type', 'payment_status', 'payment_method', 'payment_channel',
        'payment_no', 'payment_name', 'payment_fee',
        'ipaymu_trx_id', 'payment_url', 'payment_expired_at',
        'subtotal', 'tax_amount', 'discount_amount', 'grand_total',
        'sale_id', 'notes', 'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at'       => 'datetime',
        'payment_expired_at' => 'datetime',
        'subtotal'           => 'decimal:2',
        'tax_amount'         => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'grand_total'        => 'decimal:2',
        'payment_fee'        => 'decimal:2',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function session()
    {
        return $this->belongsTo(TableSession::class, 'session_id');
    }

    public function items()
    {
        return $this->hasMany(TableOrderItem::class, 'table_order_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
