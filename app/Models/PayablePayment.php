<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayablePayment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payable_id',
        'payment_date',
        'amount',
        'payment_method',
        'notes',
        'created_by',
    ];

    public function payable()
    {
        return $this->belongsTo(Payable::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
