<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseApiController
{
    // GET /api/products?search=&category_id=&is_active=
    public function index(Request $request)
    {
        $products = Product::forTenant()
            ->with('category', 'unit')
            ->when(
                $request->search,
                fn ($q) => $q->where(
                    fn ($q2) => $q2->where('name', 'like', "%{$request->search}%")
                        ->orWhere('sku', 'like', "%{$request->search}%")
                        ->orWhere('barcode', 'like', "%{$request->search}%")
                )
            )
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->is_active))
            ->paginate(20);

        return $this->ok($products);
    }

    // POST /api/products
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'min_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(
            'category_id',
            'unit_id',
            'sku',
            'barcode',
            'name',
            'description',
            'purchase_price',
            'selling_price',
            'min_stock',
            'is_active'
        );
        $data['tenant_id'] = $this->tenantId();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return $this->ok($product->load('category', 'unit'), 'Produk berhasil dibuat.', 201);
    }

    // GET /api/products/{product}
    public function show(Product $product)
    {
        abort_if($product->tenant_id !== $this->tenantId(), 403);

        return $this->ok($product->load('category', 'unit', 'stocks.store'));
    }

    // PUT /api/products/{product}
    public function update(Request $request, Product $product)
    {
        abort_if($product->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'name' => 'required|string|max:150',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'min_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(
            'category_id',
            'unit_id',
            'sku',
            'barcode',
            'name',
            'description',
            'purchase_price',
            'selling_price',
            'min_stock',
            'is_active'
        );

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return $this->ok($product->load('category', 'unit'), 'Produk diperbarui.');
    }

    // DELETE /api/products/{product}
    public function destroy(Product $product)
    {
        abort_if($product->tenant_id !== $this->tenantId(), 403);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return $this->ok(null, 'Produk dihapus.');
    }

    // GET /api/products/low-stock?store_id=
    public function lowStock(Request $request)
    {
        $storeId = $request->store_id ?? $this->storeId();
        $products = Product::forTenant()
            ->join('stocks', 'products.id', '=', 'stocks.product_id')
            ->where('stocks.store_id', $storeId)
            ->whereColumn('stocks.qty', '<=', 'products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->select('products.*', 'stocks.qty as current_stock')
            ->with('category', 'unit')
            ->get();

        return $this->ok($products);
    }

    // GET /api/products/search-by-code?code=
    public function searchByBarcodeOrSku(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->input('code');

        $product = Product::forTenant()
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->with(['category', 'unit', 'stocks.store'])
            ->first();

        if (! $product) {
            return $this->fail('Produk tidak ditemukan.', 404);
        }

        return $this->ok($product);
    }
}
