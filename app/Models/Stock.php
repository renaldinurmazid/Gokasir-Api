<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'tenant_id',
        'store_id',
        'product_id',
        'qty',
    ];

    const UPDATED_AT = 'updated_at';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
