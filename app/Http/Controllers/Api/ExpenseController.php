<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    // GET /api/expense-categories
    public function categories()
    {
        return $this->ok(ExpenseCategory::forTenant()->get());
    }

    // POST /api/expense-categories
    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $cat = ExpenseCategory::create(['tenant_id' => $this->tenantId(), 'name' => $request->name]);
        return $this->ok($cat, 'Kategori pengeluaran ditambahkan.', 201);
    }

    // GET /api/expenses
    public function index(Request $request)
    {
        $expenses = Expense::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->category_id,fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->from,       fn($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,         fn($q) => $q->whereDate('expense_date', '<=', $request->to))
            ->with('category', 'store')
            ->latest('expense_date')
            ->paginate(20);

        return $this->ok($expenses);
    }

    // POST /api/expenses
    public function store(Request $request)
    {
        $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'amount'      => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'description' => 'nullable|string',
            'expense_date'=> 'nullable|date',
        ]);

        $expense = Expense::create(array_merge(
            $request->only('store_id','category_id','amount','expense_date','description','receipt_image'),
            [
                'tenant_id'   => $this->tenantId(),
                'created_by'  => auth()->id(),
                'expense_date'=> $request->expense_date ?? now(),
                'created_at'  => now(),
            ]
        ));

        return $this->ok($expense->load('category'), 'Pengeluaran dicatat.', 201);
    }

    // PUT /api/expenses/{expense}
    public function update(Request $request, Expense $expense)
    {
        abort_if($expense->tenant_id !== $this->tenantId(), 403);
        $expense->update($request->only('category_id','amount','expense_date','description','receipt_image'));
        return $this->ok($expense, 'Pengeluaran diperbarui.');
    }

    // DELETE /api/expenses/{expense}
    public function destroy(Expense $expense)
    {
        abort_if($expense->tenant_id !== $this->tenantId(), 403);
        $expense->delete();
        return $this->ok(null, 'Pengeluaran dihapus.');
    }
}
