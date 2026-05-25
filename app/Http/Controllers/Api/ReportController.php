<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Receivable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseApiController
{
    // GET /api/reports/summary?store_id=&from=&to=
    public function summary(Request $request)
    {
        $tenantId = $this->tenantId();
        $storeId  = $request->store_id;
        $from     = $request->from ?? now()->startOfMonth()->toDateString();
        $to       = $request->to   ?? now()->toDateString();

        $salesQ = Sale::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $totalRevenue  = (clone $salesQ)->sum('grand_total');
        $totalPaid     = (clone $salesQ)->sum('paid_amount');
        $totalDiscount = (clone $salesQ)->sum('discount_amount');
        $totalTax      = (clone $salesQ)->sum('tax_amount');
        $totalTrx      = (clone $salesQ)->count();

        $totalExpense = Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->sum('amount');

        $totalReceivable = Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->when($storeId, fn($q) => $q->whereHas('sale', fn($q2) => $q2->where('store_id', $storeId)))
            ->sum('remaining_amount');

        return $this->ok([
            'period'            => ['from' => $from, 'to' => $to],
            'total_transaction' => $totalTrx,
            'total_revenue'     => $totalRevenue,
            'total_paid'        => $totalPaid,
            'total_discount'    => $totalDiscount,
            'total_tax'         => $totalTax,
            'total_expense'     => $totalExpense,
            'net_income'        => $totalRevenue - $totalExpense,
            'outstanding_receivable' => $totalReceivable,
        ]);
    }

    // GET /api/reports/sales-by-day?store_id=&from=&to=
    public function salesByDay(Request $request)
    {
        $data = Sale::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->whereDate('transaction_date', '>=', $request->from ?? now()->startOfMonth())
            ->whereDate('transaction_date', '<=', $request->to   ?? now())
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(grand_total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/top-products?store_id=&from=&to=&limit=10
    public function topProducts(Request $request)
    {
        $limit = $request->limit ?? 10;

        $data = SaleItem::join('sales', 'sales_items.sale_id', '=', 'sales.id')
            ->join('products', 'sales_items.product_id', '=', 'products.id')
            ->where('sales.tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('sales.store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('sales.transaction_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('sales.transaction_date', '<=', $request->to))
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sales_items.qty) as total_qty'),
                DB::raw('SUM(sales_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/sales-by-payment?store_id=&from=&to=
    public function salesByPaymentMethod(Request $request)
    {
        $data = Sale::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->select('payment_method', DB::raw('SUM(grand_total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/stock-value?store_id=
    public function stockValue(Request $request)
    {
        $storeId = $request->store_id ?? $this->storeId();

        $data = Stock::where('stocks.store_id', $storeId)
            ->where('stocks.tenant_id', $this->tenantId())
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.purchase_price',
                'products.selling_price',
                'stocks.qty',
                DB::raw('stocks.qty * products.purchase_price as purchase_value'),
                DB::raw('stocks.qty * products.selling_price as selling_value')
            )
            ->get();

        $summary = [
            'total_purchase_value' => $data->sum('purchase_value'),
            'total_selling_value'  => $data->sum('selling_value'),
            'items'                => $data,
        ];

        return $this->ok($summary);
    }

    // GET /api/reports/cashier-performance?store_id=&from=&to=
    public function cashierPerformance(Request $request)
    {
        $data = Sale::where('sales.tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('sales.store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('sales.transaction_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('sales.transaction_date', '<=', $request->to))
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(sales.id) as total_transaction'),
                DB::raw('SUM(sales.grand_total) as total_revenue')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/expense-by-category?store_id=&from=&to=
    public function expenseByCategory(Request $request)
    {
        $data = Expense::where('expenses.tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('expense_date', '<=', $request->to))
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as category',
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return $this->ok($data);
    }
}
