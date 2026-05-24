<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenPricing extends Model
{
    protected $table = 'token_pricing';

    protected $fillable = [
        'type', 'name', 'description', 'price',
        'token_amount', 'token_bonus', 'is_active',
        'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'price'        => 'decimal:2',
    ];

    // Total token yang diterima pembeli
    public function getTotalTokenAttribute(): int
    {
        return $this->token_amount + $this->token_bonus;
    }

    // Harga per token efektif
    public function getPricePerTokenAttribute(): float
    {
        $totalToken = $this->total_token;
        if ($totalToken === 0) return 0;
        return round($this->price / $totalToken, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
