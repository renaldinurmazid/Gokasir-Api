<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseApiController
{
    // GET /api/suppliers?store_id=&search=
    public function index(Request $request)
    {
        $suppliers = Supplier::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->when($request->search, fn($q) =>
                $q->where(fn($sq) =>
                    $sq->where('name', 'like', "%{$request->search}%")
                      ->orWhere('phone', 'like', "%{$request->search}%")
                      ->orWhere('code', 'like', "%{$request->search}%")
                )
            )
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->withCount('purchases')
            ->paginate(20);

        return $this->ok($suppliers);
    }

    // POST /api/suppliers
    public function store(Request $request)
    {
        $request->validate([
            'store_id'     => 'required|exists:stores,id',
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $supplier = Supplier::create(array_merge(
            $request->only('store_id', 'name', 'code', 'contact_person',
                           'phone', 'email', 'address', 'city', 'credit_limit', 'notes'),
            ['tenant_id' => $this->tenantId()]
        ));

        return $this->ok($supplier, 'Supplier ditambahkan.', 201);
    }

    // GET /api/suppliers/{supplier}
    public function show(Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        return $this->ok($supplier->load('products'));
    }

    // PUT /api/suppliers/{supplier}
    public function update(Request $request, Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        $supplier->update($request->only(
            'name', 'code', 'contact_person', 'phone', 'email',
            'address', 'city', 'credit_limit', 'notes', 'is_active'
        ));

        return $this->ok($supplier, 'Supplier diperbarui.');
    }

    // DELETE /api/suppliers/{supplier}
    public function destroy(Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);
        $supplier->delete();
        return $this->ok(null, 'Supplier dihapus.');
    }

    // GET /api/suppliers/{supplier}/history — histori transaksi supplier
    public function history(Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        return $this->ok([
            'supplier'       => $supplier,
            'purchases'      => $supplier->purchases()
                                    ->with('items.product')
                                    ->latest('purchase_date')
                                    ->paginate(10),
            'returns'        => $supplier->purchaseReturns()
                                    ->with('items.product')
                                    ->latest('return_date')
                                    ->paginate(10),
            'payables'       => $supplier->payables()
                                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                                    ->get(),
            'total_purchase' => $supplier->purchases()->sum('grand_total'),
            'total_return'   => $supplier->purchaseReturns()->sum('total_amount'),
            'current_debt'   => $supplier->current_debt,
        ]);
    }

    // POST /api/suppliers/{supplier}/products — tambah relasi produk ke supplier
    public function attachProduct(Request $request, Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'purchase_price' => 'required|numeric|min:0',
            'supplier_sku'   => 'nullable|string|max:100',
            'min_order_qty'  => 'nullable|integer|min:1',
            'is_preferred'   => 'nullable|boolean',
        ]);

        // Jika is_preferred = true, reset preferred lain untuk produk ini di store ini
        if ($request->is_preferred) {
            \App\Models\ProductSupplier::where('store_id', $supplier->store_id)
                ->where('product_id', $request->product_id)
                ->update(['is_preferred' => false]);
        }

        $supplier->products()->syncWithoutDetaching([
            $request->product_id => [
                'tenant_id'      => $this->tenantId(),
                'store_id'       => $supplier->store_id,
                'purchase_price' => $request->purchase_price,
                'supplier_sku'   => $request->supplier_sku,
                'min_order_qty'  => $request->min_order_qty ?? 1,
                'is_preferred'   => $request->is_preferred ?? false,
            ]
        ]);

        return $this->ok(null, 'Produk dikaitkan ke supplier.');
    }

    // DELETE /api/suppliers/{supplier}/products/{productId}
    public function detachProduct(Supplier $supplier, int $productId)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);
        $supplier->products()->detach($productId);
        return $this->ok(null, 'Produk dilepas dari supplier.');
    }
}
