<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Store extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $appends = [
        'logo_url',
        'banner_url',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'logo',
        'banner',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'receipt_footer',
    ];

    public function getBannerUrlAttribute()
    {
        if ($this->banner) {
            return asset('storage/' . $this->banner);
        }
        return null;
    }
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
