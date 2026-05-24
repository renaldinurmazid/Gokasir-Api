<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Payable;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends BaseApiController
{
    // GET /api/purchases?store_id=&supplier_id=&payment_status=&from=&to=
    public function index(Request $request)
    {
        $purchases = Purchase::where('tenant_id', $this->tenantId())
            ->when($request->store_id,      fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->supplier_id,   fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->payment_status,fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from, fn($q) => $q->whereDate('purchase_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('purchase_date', '<=', $request->to))
            ->with('supplier', 'store', 'createdBy')
            ->latest('purchase_date')
            ->paginate(20);

        return $this->ok($purchases);
    }

    // POST /api/purchases
    public function store(Request $request)
    {
        $request->validate([
            'store_id'        => 'required|exists:stores,id',
            'supplier_id'     => 'required|exists:suppliers,id',
            'purchase_date'   => 'required|date',
            'payment_method'  => 'required|in:cash,transfer,tempo',
            'paid_amount'     => 'required|numeric|min:0',
            'due_date'        => 'nullable|date',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Hitung total
            $subtotal = collect($request->items)->sum(
                fn($i) => ($i['price'] * $i['qty']) - ($i['discount'] ?? 0)
            );
            $discount      = $request->discount_amount ?? 0;
            $tax           = $request->tax_amount ?? 0;
            $grandTotal    = $subtotal - $discount + $tax;
            $paidAmount    = $request->paid_amount;
            $remaining     = max(0, $grandTotal - $paidAmount);
            $payStatus     = $remaining <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            if ($request->payment_method === 'tempo') {
                $payStatus = 'unpaid';
                $remaining = $grandTotal;
                $paidAmount = 0;
            }

            $purchaseNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $purchase = Purchase::create([
                'tenant_id'       => $this->tenantId(),
                'store_id'        => $request->store_id,
                'supplier_id'     => $request->supplier_id,
                'purchase_number' => $purchaseNumber,
                'supplier_invoice'=> $request->supplier_invoice,
                'purchase_date'   => $request->purchase_date,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'remaining_amount'=> $remaining,
                'payment_method'  => $request->payment_method,
                'payment_status'  => $payStatus,
                'receive_status'  => 'received',
                'due_date'        => $request->due_date,
                'notes'           => $request->notes,
                'created_by'      => auth()->id(),
                'created_at'      => now(),
            ]);

            // Simpan items + tambah stok
            foreach ($request->items as $item) {
                $disc    = $item['discount'] ?? 0;
                $itemSub = ($item['price'] * $item['qty']) - $disc;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'qty'         => $item['qty'],
                    'qty_received'=> $item['qty'],
                    'price'       => $item['price'],
                    'discount'    => $disc,
                    'subtotal'    => $itemSub,
                ]);

                // Tambah stok
                $stock = Stock::firstOrCreate(
                    ['store_id' => $request->store_id, 'product_id' => $item['product_id']],
                    ['tenant_id' => $this->tenantId(), 'qty' => 0]
                );
                $stockBefore = $stock->qty;
                $stock->increment('qty', $item['qty']);
                $stock->refresh();

                StockMovement::create([
                    'tenant_id'      => $this->tenantId(),
                    'store_id'       => $request->store_id,
                    'product_id'     => $item['product_id'],
                    'type'           => 'in',
                    'qty'            => $item['qty'],
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stock->qty,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchase->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            // Buat payable jika ada sisa hutang
            if ($remaining > 0) {
                Payable::create([
                    'tenant_id'       => $this->tenantId(),
                    'store_id'        => $request->store_id,
                    'supplier_id'     => $request->supplier_id,
                    'purchase_id'     => $purchase->id,
                    'total_amount'    => $grandTotal,
                    'paid_amount'     => $paidAmount,
                    'remaining_amount'=> $remaining,
                    'due_date'        => $request->due_date,
                    'status'          => $payStatus,
                    'created_at'      => now(),
                ]);

                // Update current_debt supplier
                Supplier::where('id', $request->supplier_id)
                    ->increment('current_debt', $remaining);
            }

            DB::commit();
            return $this->ok($purchase->load('items.product', 'supplier'), 'Pembelian dicatat.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat pembelian: ' . $e->getMessage(), 500);
        }
    }

    // GET /api/purchases/{id}
    public function show(Purchase $purchase)
    {
        abort_if($purchase->tenant_id !== $this->tenantId(), 403);
        return $this->ok($purchase->load('items.product.unit', 'supplier', 'store', 'payable', 'returns'));
    }
}
