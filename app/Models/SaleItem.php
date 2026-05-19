<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;
    protected $table = 'sales_items';

    protected $appends = [
        'price_formatted',
        'discount_formatted',
        'subtotal_formatted',
    ];

    public function getPriceFormattedAttribute()
    {
        return 'Rp' . number_format($this->price ?? 0, 0, ',', '.');
    }

    public function getDiscountFormattedAttribute()
    {
        return 'Rp' . number_format($this->discount ?? 0, 0, ',', '.');
    }

    public function getSubtotalFormattedAttribute()
    {
        return 'Rp' . number_format($this->subtotal ?? 0, 0, ',', '.');
    }
    protected $fillable = [
        'sale_id',
        'product_id',
        'qty',
        'price',
        'discount',
        'subtotal',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
