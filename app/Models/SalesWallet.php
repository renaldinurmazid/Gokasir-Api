<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesWallet extends Model
{
    protected $fillable = ['sales_id', 'balance'];

    public function user()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function transactions()
    {
        return $this->hasMany(SalesWalletTransaction::class);
    }
}
