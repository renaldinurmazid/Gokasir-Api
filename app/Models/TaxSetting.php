<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'tax_rate', 'tax_enabled',
        'tax_name', 'tax_inclusive', 'updated_by',
    ];

    protected $casts = [
        'tax_enabled'   => 'boolean',
        'tax_inclusive' => 'boolean',
        'tax_rate'      => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
