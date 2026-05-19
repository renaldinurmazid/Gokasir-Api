<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Expense extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'store_id',
        'category_id',
        'amount',
        'expense_date',
        'description',
        'receipt_image',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
