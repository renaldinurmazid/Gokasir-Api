<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableOrder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index($tableCode)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with('store')
            ->firstOrFail();

        $recommendedProducts = Product::where('tenant_id', $table->tenant_id)
            ->where('is_active', true)
            ->limit(4)
            ->get();

        $categories = Category::where('tenant_id', $table->tenant_id)
            ->whereHas('products', function ($query) {
                $query->where('is_active', true);
            })
            ->with(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        // Append photo_url explicitly
        $recommendedProducts->each->append('photo_url');
        $categories->each(function($category) {
            $category->products->each->append('photo_url');
        });

        return Inertia::render('Orders/Index', [
            'tableCode'           => $tableCode,
            'table'               => $table,
            'recommendedProducts' => $recommendedProducts,
            'categories'          => $categories,
        ]);
    }

    public function search(Request $request, $tableCode)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with('store')
            ->firstOrFail();

        $searchQuery = $request->query('q', '');

        if ($searchQuery !== '') {
            $products = Product::where('tenant_id', $table->tenant_id)
                ->where('is_active', true)
                ->where('name', 'like', "%{$searchQuery}%")
                ->get();
        } else {
            $products = collect();
        }

        $products->each->append('photo_url');

        return Inertia::render('Orders/Search', [
            'tableCode'   => $tableCode,
            'table'       => $table,
            'products'    => $products,
            'searchQuery' => $searchQuery,
        ]);
    }

    public function showCategory($tableCode, $categoryName)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with('store')
            ->firstOrFail();

        $category = Category::where('tenant_id', $table->tenant_id)
            ->where('name', $categoryName)
            ->firstOrFail();

        $products = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->get();

        $products->each->append('photo_url');

        return Inertia::render('Orders/ShowCategory', [
            'tableCode' => $tableCode,
            'table'     => $table,
            'category'  => $category,
            'products'  => $products,
        ]);
    }

    public function checkout($tableCode)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with(['store.tenant.taxSetting'])
            ->firstOrFail();

        return Inertia::render('Orders/Checkout', [
            'tableCode' => $tableCode,
            'table'     => $table,
        ]);
    }

    public function status($tableCode, $orderNumber)
    {
        $table = Table::where('code', $tableCode)->firstOrFail();
        
        // Fetch order details directly from database to avoid HTTP roundtrip deadlocks in single-threaded environments
        $order = TableOrder::where('order_number', $orderNumber)
            ->where('table_id', $table->id)
            ->with('items.product')
            ->firstOrFail();

        $order->items->each(function($item) {
            if ($item->product) {
                $item->product->append('photo_url');
            }
        });

        return Inertia::render('Orders/Status', [
            'tableCode'   => $tableCode,
            'table'       => $table,
            'orderNumber' => $orderNumber,
            'order'       => $order,
        ]);
    }

    public function cancel($tableCode, $orderNumber)
    {
        return redirect()->route('web-order', $tableCode)->with('error', 'Pembayaran pesanan dibatalkan.');
    }

    public function history($tableCode)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with('store')
            ->firstOrFail();

        return Inertia::render('Orders/History', [
            'tableCode' => $tableCode,
            'table'     => $table,
        ]);
    }
}
