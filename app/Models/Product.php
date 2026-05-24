<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $appends = [
        'photo_url',
        'stock_at_store',
        'purchase_price_formatted',
        'selling_price_formatted',
    ];

    public function getStockAtStoreAttribute()
    {
        $storeId = request('store_id') ?? auth()->user()?->store_id;
        
        if ($storeId) {
            return $this->stockAtStore($storeId);
        }
        
        // Fallback: jika tidak ada store_id di request/user, gunakan stok pertama yang tersedia
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->first()?->qty ?? 0;
        }
        
        return (int) $this->stocks()->first()?->qty ?? 0;
    }

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

    public function getPhotoUrlAttribute()
    {
        if ($this->image) {
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            return asset('storage/' . $this->image);
        }
        return null;
    }

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
        if ($this->relationLoaded('stocks')) {
            return $this->stocks->firstWhere('store_id', $storeId)?->qty ?? 0;
        }
        return $this->stocks()->where('store_id', $storeId)->first()?->qty ?? 0;
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_suppliers')
            ->withPivot('purchase_price', 'supplier_sku', 'min_order_qty', 'is_preferred')
            ->withTimestamps();
    }

    public function preferredSupplier(int $storeId): ?Supplier
    {
        return $this->suppliers()
            ->wherePivot('store_id', $storeId)
            ->wherePivot('is_preferred', true)
            ->first();
    }
}
