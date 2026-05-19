<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Sale extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $appends = [
        'subtotal_formatted',
        'discount_amount_formatted',
        'tax_amount_formatted',
        'grand_total_formatted',
        'paid_amount_formatted',
        'change_amount_formatted',
    ];

    public function getSubtotalFormattedAttribute()
    {
        return 'Rp' . number_format($this->subtotal ?? 0, 0, ',', '.');
    }

    public function getDiscountAmountFormattedAttribute()
    {
        return 'Rp' . number_format($this->discount_amount ?? 0, 0, ',', '.');
    }

    public function getTaxAmountFormattedAttribute()
    {
        return 'Rp' . number_format($this->tax_amount ?? 0, 0, ',', '.');
    }

    public function getGrandTotalFormattedAttribute()
    {
        return 'Rp' . number_format($this->grand_total ?? 0, 0, ',', '.');
    }

    public function getPaidAmountFormattedAttribute()
    {
        return 'Rp' . number_format($this->paid_amount ?? 0, 0, ',', '.');
    }

    public function getChangeAmountFormattedAttribute()
    {
        return 'Rp' . number_format($this->change_amount ?? 0, 0, ',', '.');
    }

    protected $fillable = [
        'tenant_id',
        'store_id',
        'invoice_number',
        'customer_id',
        'cashier_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_status',
        'notes',
        'transaction_date',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }
}
