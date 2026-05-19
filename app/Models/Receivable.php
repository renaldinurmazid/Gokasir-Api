<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sale_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
    ];

    public function payments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
