<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ProductSupplier extends Model
{
    use BelongsToTenant;

    protected $table = 'product_suppliers';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'product_id',
        'supplier_id',
        'purchase_price',
        'supplier_sku',
        'min_order_qty',
        'is_preferred',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
