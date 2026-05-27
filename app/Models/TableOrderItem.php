<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'table_order_id', 'product_id',
        'qty', 'price', 'discount', 'subtotal', 'notes', 'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
