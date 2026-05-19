<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Customer extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'address',
        'credit_limit',
        'current_debt',
    ];

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }
}
