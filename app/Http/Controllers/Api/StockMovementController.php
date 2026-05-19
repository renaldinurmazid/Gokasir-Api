<?php

namespace App\Http\Controllers\Api;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends BaseApiController
{
    // GET /api/stock-movements?store_id=&product_id=&type=&from=&to=
    public function index(Request $request)
    {
        $movements = StockMovement::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type,       fn($q) => $q->where('type', $request->type))
            ->when($request->from,       fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,         fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->with('product', 'store', 'createdBy')
            ->latest('created_at')
            ->paginate(30);

        return $this->ok($movements);
    }

    // POST /api/stock-movements  (in / out / adjustment)
    public function store(Request $request)
    {
        $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:in,out,adjustment',
            'qty'        => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stock = Stock::firstOrCreate(
                ['store_id' => $request->store_id, 'product_id' => $request->product_id],
                ['tenant_id' => $this->tenantId(), 'qty' => 0]
            );

            $stockBefore = $stock->qty;

            if ($request->type === 'in') {
                $stock->increment('qty', $request->qty);
            } elseif ($request->type === 'out') {
                if ($stock->qty < $request->qty) {
                    return $this->fail('Stok tidak mencukupi.', 422);
                }
                $stock->decrement('qty', $request->qty);
            } else {
                // adjustment: set langsung
                $stock->update(['qty' => $request->qty]);
            }

            $stock->refresh();

            StockMovement::create([
                'tenant_id'      => $this->tenantId(),
                'store_id'       => $request->store_id,
                'product_id'     => $request->product_id,
                'type'           => $request->type,
                'qty'            => $request->qty,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stock->qty,
                'reference_type' => $request->reference_type,
                'reference_id'   => $request->reference_id,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
            ]);

            DB::commit();
            return $this->ok($stock, 'Mutasi stok berhasil.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal memperbarui stok: ' . $e->getMessage(), 500);
        }
    }
}
