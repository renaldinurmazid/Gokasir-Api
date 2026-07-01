<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesWalletTransaction extends Model
{
    protected $fillable = [
        'sales_wallet_id', 'type', 'amount', 'reference_type', 'reference_id', 
        'description', 'balance_before', 'balance_after'
    ];

    public function wallet()
    {
        return $this->belongsTo(SalesWallet::class, 'sales_wallet_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
