<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'store_id',
        'role',
        'name',
        'email',
        'phone',
        'password',
        'otp_code',
        'otp_expires_at',
        'status',
        'is_approved',
        'last_login',
        'referral_code',
        'referred_by_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_id');
    }

    public function salesWallet()
    {
        return $this->hasOne(SalesWallet::class, 'sales_id');
    }

    public function addSalesWalletCommission($amount, $type, $referenceModel, $description = null)
    {
        if ($this->role !== 'sales') {
            return false;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($amount, $type, $referenceModel, $description) {
            $wallet = $this->salesWallet()->firstOrCreate(
                ['sales_id' => $this->id],
                ['balance' => 0]
            );

            // Lock the row for update
            $wallet = SalesWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $wallet->transactions()->create([
                'type'           => $type,
                'amount'         => $amount,
                'reference_type' => get_class($referenceModel),
                'reference_id'   => $referenceModel->id,
                'description'    => $description,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
            ]);
        });

        return true;
    }
}
