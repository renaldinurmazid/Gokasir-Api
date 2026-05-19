<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ExpenseCategory extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'name'];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
