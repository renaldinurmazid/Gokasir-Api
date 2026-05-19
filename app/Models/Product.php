<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $appends = [
        'purchase_price_formatted',
        'selling_price_formatted',
    ];

    public function getPurchasePriceFormattedAttribute()
    {
        return 'Rp' . number_format($this->purchase_price ?? 0, 0, ',', '.');
    }

    public function getSellingPriceFormattedAttribute()
    {
        return 'Rp' . number_format($this->selling_price ?? 0, 0, ',', '.');
    }

    protected $fillable = [
        'tenant_id',
        'category_id',
        'unit_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image',
        'purchase_price',
        'selling_price',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function stockAtStore($storeId)
    {
        return $this->stocks()->where('store_id', $storeId)->first()?->qty ?? 0;
    }
}
