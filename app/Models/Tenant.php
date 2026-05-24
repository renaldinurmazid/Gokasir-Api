<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'business_type',
        'email',
        'phone',
        'subscription_plan',
        'status',
        'expired_at',
        'tax_rate',
        'token_balance',
        'token_lifetime_used',
        'token_lifetime_topup',
        'harga_token',
    ];

    protected $casts = [
        'harga_token' => 'float',
    ];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function taxSetting()
    {
        return $this->hasOne(TaxSetting::class);
    }

    public function tokenTopups()
    {
        return $this->hasMany(TokenTopup::class);
    }

    public function tokenLogs()
    {
        return $this->hasMany(TokenUsageLog::class);
    }

    // Ambil tax setting atau return default
    public function getActiveTaxSetting(): TaxSetting
    {
        return $this->taxSetting ?? new TaxSetting([
            'tax_rate'      => $this->tax_rate ?? 12.00,
            'tax_enabled'   => true,
            'tax_name'      => 'PPN',
            'tax_inclusive' => false,
        ]);
    }

    // Cek apakah masih punya token
    public function hasToken(): bool
    {
        return $this->token_balance > 0;
    }

    // Kurangi token (thread-safe dengan DB lock)
    public function deductToken(int $amount = 1): bool
    {
        if ($this->token_balance < $amount) return false;

        static::where('id', $this->id)
            ->where('token_balance', '>=', $amount)
            ->update([
                'token_balance'       => \DB::raw("token_balance - {$amount}"),
                'token_lifetime_used' => \DB::raw("token_lifetime_used + {$amount}"),
            ]);

        $this->refresh();
        return true;
    }

    // Tambah token
    public function addToken(int $amount): void
    {
        static::where('id', $this->id)->update([
            'token_balance'        => \DB::raw("token_balance + {$amount}"),
            'token_lifetime_topup' => \DB::raw("token_lifetime_topup + {$amount}"),
        ]);
        $this->refresh();
    }

    /**
     * Ambil harga token efektif untuk tenant ini.
     * Jika harga_token > 0, pakai harga mitra.
     * Jika tidak, fallback ke harga master dari token_pricing.
     */
    public function getEffectiveTokenPrice(\App\Models\TokenPricing $pricing): float
    {
        if ($this->harga_token > 0) {
            return (float) $this->harga_token;
        }

        return (float) $pricing->price; // harga master
    }

    /**
     * Apakah tenant ini punya harga negosiasi?
     */
    public function hasMitraPrice(): bool
    {
        return $this->harga_token > 0;
    }
}
