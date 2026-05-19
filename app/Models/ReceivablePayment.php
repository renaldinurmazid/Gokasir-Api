<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivablePayment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'receivable_id',
        'payment_date',
        'amount',
        'payment_method',
        'notes',
        'created_by',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
