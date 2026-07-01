<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesActivationPrice extends Model
{
    protected $fillable = [
        'sales_id',
        'token_pricing_id',
        'custom_price',
    ];

    protected $casts = [
        'custom_price' => 'float',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function tokenPricing()
    {
        return $this->belongsTo(TokenPricing::class, 'token_pricing_id');
    }
}
