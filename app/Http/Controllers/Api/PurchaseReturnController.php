<?php

namespace App\Http\Controllers\Api;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Payable;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends BaseApiController
{
    // GET /api/purchase-returns?store_id=&supplier_id=
    public function index(Request $request)
    {
        $returns = PurchaseReturn::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->supplier_id,fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->with('supplier', 'purchase')
            ->latest('return_date')
            ->paginate(20);

        return $this->ok($returns);
    }

    // POST /api/purchase-returns
    public function store(Request $request)
    {
        $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'return_date' => 'required|date',
            'resolution'  => 'required|in:refund,debt_reduction',
            'reason'      => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = collect($request->items)
                ->sum(fn($i) => $i['price'] * $i['qty']);

            $returnNumber = 'RTN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $return = PurchaseReturn::create([
                'tenant_id'   => $this->tenantId(),
                'store_id'    => $request->store_id,
                'supplier_id' => $request->supplier_id,
                'purchase_id' => $request->purchase_id,
                'return_number'=> $returnNumber,
                'return_date' => $request->return_date,
                'total_amount'=> $totalAmount,
                'resolution'  => $request->resolution,
                'status'      => 'confirmed',
                'reason'      => $request->reason,
                'notes'       => $request->notes,
                'created_by'  => auth()->id(),
                'created_at'  => now(),
            ]);

            foreach ($request->items as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'         => $item['product_id'],
                    'qty'                => $item['qty'],
                    'price'              => $item['price'],
                    'subtotal'           => $item['price'] * $item['qty'],
                    'reason'             => $item['reason'] ?? null,
                ]);

                // Kurangi stok (barang dikembalikan ke supplier)
                $stock = Stock::where('store_id', $request->store_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($stock) {
                    $stockBefore = $stock->qty;
                    $stock->decrement('qty', $item['qty']);
                    $stock->refresh();

                    StockMovement::create([
                        'tenant_id'      => $this->tenantId(),
                        'store_id'       => $request->store_id,
                        'product_id'     => $item['product_id'],
                        'type'           => 'out',
                        'qty'            => $item['qty'],
                        'stock_before'   => $stockBefore,
                        'stock_after'    => $stock->qty,
                        'reference_type' => 'purchase_return',
                        'reference_id'   => $return->id,
                        'created_by'     => auth()->id(),
                        'created_at'     => now(),
                    ]);
                }
            }

            // Jika resolusi = pengurang hutang, update payable
            if ($request->resolution === 'debt_reduction' && $request->purchase_id) {
                $payable = Payable::where('purchase_id', $request->purchase_id)->first();

                if ($payable && $payable->remaining_amount > 0) {
                    $reduction    = min($totalAmount, $payable->remaining_amount);
                    $newPaid      = $payable->paid_amount + $reduction;
                    $newRemaining = max(0, $payable->remaining_amount - $reduction);
                    $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

                    $payable->update([
                        'paid_amount'      => $newPaid,
                        'remaining_amount' => $newRemaining,
                        'status'           => $newStatus,
                    ]);

                    Supplier::where('id', $request->supplier_id)
                        ->decrement('current_debt', $reduction);
                }
            }

            DB::commit();
            return $this->ok($return->load('items.product', 'supplier'), 'Retur dicatat.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat retur: ' . $e->getMessage(), 500);
        }
    }
}
