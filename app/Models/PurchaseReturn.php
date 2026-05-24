<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PurchaseReturn extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'store_id',
        'supplier_id',
        'purchase_id',
        'return_number',
        'return_date',
        'total_amount',
        'resolution',
        'status',
        'reason',
        'notes',
        'created_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
