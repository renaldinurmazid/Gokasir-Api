<?php

namespace App\Http\Controllers\Api;

use App\Models\TableOrder;
use App\Models\TableSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableOrderController extends BaseApiController
{
    public function __construct(protected TokenService $tokenService) {}

    /**
     * GET /api/table-orders?store_id=&status=&table_id=
     * Kasir lihat semua pesanan masuk
     */
    public function index(Request $request)
    {
        $orders = TableOrder::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->table_id, fn($q) => $q->where('table_id', $request->table_id))
            ->with('table', 'items.product', 'session', 'confirmedBy')
            ->latest()
            ->paginate(30);

        return $this->ok($orders);
    }

    /**
     * GET /api/table-orders/pending
     * Shortcut: semua pesanan pending yang butuh dikonfirmasi kasir
     */
    public function pending(Request $request)
    {
        $orders = TableOrder::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->where('status', 'pending')
            ->with('table', 'items.product', 'session')
            ->oldest()
            ->get();

        return $this->ok($orders);
    }

    /**
     * GET /api/table-orders/{table_order}
     * Detail pesanan
     */
    public function show(TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);
        
        return $this->ok($tableOrder->load('table', 'items.product', 'session', 'confirmedBy'));
    }

    /**
     * POST /api/table-orders/{table_order}/confirm
     * Kasir konfirmasi pesanan → status: confirmed
     */
    public function confirm(TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if (!$tableOrder->isPending()) {
            return $this->fail('Pesanan sudah dikonfirmasi atau tidak dalam status pending.', 422);
        }

        $tableOrder->update([
            'status'       => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        // Update status item
        $tableOrder->items()->update(['status' => 'confirmed']);

        return $this->ok($tableOrder->load('items.product', 'table'), 'Pesanan dikonfirmasi.');
    }

    /**
     * POST /api/table-orders/{table_order}/cancel
     * Kasir batalkan pesanan
     */
    public function cancel(Request $request, TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if ($tableOrder->isPaid()) {
            return $this->fail('Pesanan yang sudah dibayar tidak bisa dibatalkan.', 422);
        }

        $tableOrder->update(['status' => 'cancelled']);
        $tableOrder->items()->update(['status' => 'cancelled']);

        return $this->ok(null, 'Pesanan dibatalkan.');
    }

    /**
     * POST /api/table-orders/{table_order}/process-payment
     * Kasir proses pembayaran CASH (setelah customer datang ke kasir)
     * → convert TableOrder menjadi Sale
     */
    public function processPayment(Request $request, TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if (!$tableOrder->isPending()) {
            return $this->fail('Hanya pesanan berstatus pending yang dapat diproses pembayarannya.', 422);
        }

        if ($tableOrder->isPaid()) {
            return $this->fail('Pesanan ini sudah dibayar.', 422);
        }

        $request->validate([
            'paid_amount'    => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,qris,transfer,debit,credit',
        ]);

        // Cek token
        $tenant = auth()->user()->tenant;
        if (!$tenant->hasToken()) {
            return $this->fail('Saldo token habis. Silakan topup token.', 402);
        }

        DB::beginTransaction();
        try {
            $grandTotal  = $tableOrder->grand_total;
            $paidAmount  = $request->paid_amount;
            $change      = max(0, $paidAmount - $grandTotal);
            $invoiceNum  = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            // Buat Sale dari TableOrder
            $sale = Sale::create([
                'tenant_id'       => $this->tenantId(),
                'store_id'        => $tableOrder->store_id,
                'invoice_number'  => $invoiceNum,
                'customer_id'     => null,
                'cashier_id'      => auth()->id(),
                'subtotal'        => $tableOrder->subtotal,
                'discount_amount' => $tableOrder->discount_amount,
                'tax_amount'      => $tableOrder->tax_amount,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'change_amount'   => $change,
                'payment_method'  => $request->payment_method,
                'payment_status'  => 'paid',
                'notes'           => 'Order dari Meja: ' . $tableOrder->table->name . ' | ' . $tableOrder->order_number,
                'transaction_date'=> now(),
                'created_at'      => now(),
            ]);

            // Pindahkan items ke sale_items + kurangi stok
            foreach ($tableOrder->items()->get() as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item->product_id,
                    'qty'        => $item->qty,
                    'price'      => $item->price,
                    'discount'   => $item->discount,
                    'subtotal'   => $item->subtotal,
                ]);

                $stock = Stock::firstOrCreate(
                    ['store_id' => $tableOrder->store_id, 'product_id' => $item->product_id],
                    ['tenant_id' => $this->tenantId(), 'qty' => 0]
                );
                $stockBefore = $stock->qty;
                $stock->decrement('qty', $item->qty);
                $stock->refresh();

                StockMovement::create([
                    'tenant_id'      => $this->tenantId(),
                    'store_id'       => $tableOrder->store_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'out',
                    'qty'            => $item->qty,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stock->qty,
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            // Update TableOrder → confirmed
            $tableOrder->update([
                'status'         => 'confirmed',
                'confirmed_by'   => auth()->id(),
                'confirmed_at'   => now(),
                'payment_status' => 'paid',
                'sale_id'        => $sale->id,
            ]);

            $tableOrder->items()->update(['status' => 'confirmed']);

            // Update status sesi
            $tableOrder->session->update([
                'status'    => 'paid',
            ]);

            // Potong token
            $this->tokenService->deductForSale(
                $tenant,
                $sale->id,
                $tableOrder->store_id,
                auth()->id()
            );

            DB::commit();
            
            return $this->ok([
                'sale'         => $sale->load('items.product'),
                'change'       => $change,
                'table_name'   => $tableOrder->table->name,
                'order_number' => $tableOrder->order_number,
            ], 'Pembayaran berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal memproses pembayaran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/table-orders/{table_order}/complete
     * Kasir selesaikan pesanan (masakan sudah jadi & disajikan) -> status: completed
     */
    public function complete(TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if (!$tableOrder->isConfirmed()) {
            return $this->fail('Pesanan harus dalam status confirmed sebelum diselesaikan.', 422);
        }

        DB::beginTransaction();
        try {
            $tableOrder->update([
                'status' => 'completed',
            ]);

            $tableOrder->session->update([
                'closed_at' => now(),
            ]);

            DB::commit();
            return $this->ok($tableOrder->load('items.product', 'table'), 'Pesanan selesai dan ditutup.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal menyelesaikan pesanan: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if (!$tableOrder->isPending()) {
            return $this->fail('Hanya pesanan berstatus pending yang dapat diubah.', 422);
        }

        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.1',
            'items.*.notes'      => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        $taxSetting = $tableOrder->table->store->tenant->getActiveTaxSetting();

        $subtotal = 0;
        $itemsData = [];

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', true)
                ->firstOrFail();

            $itemSubtotal = $product->selling_price * $item['qty'];
            $subtotal    += $itemSubtotal;

            $itemsData[] = [
                'product_id' => $product->id,
                'qty'        => $item['qty'],
                'price'      => $product->selling_price,
                'discount'   => 0,
                'subtotal'   => $itemSubtotal,
                'notes'      => $item['notes'] ?? null,
                'status'     => 'pending',
            ];
        }

        $taxAmount  = $taxSetting->tax_enabled
            ? round($subtotal * ($taxSetting->tax_rate / 100), 2)
            : 0;
        $grandTotal = $subtotal + $taxAmount;

        DB::beginTransaction();
        try {
            // Delete old items
            $tableOrder->items()->delete();

            // Create new items
            foreach ($itemsData as $item) {
                $tableOrder->items()->create($item);
            }

            // Update order totals
            $tableOrder->update([
                'subtotal'   => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total'=> $grandTotal,
                'notes'      => $request->notes ?? $tableOrder->notes,
            ]);

            DB::commit();

            return $this->ok(
                $tableOrder->load('items.product', 'table', 'session'),
                'Pesanan meja berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal memperbarui pesanan: ' . $e->getMessage(), 500);
        }
    }
}
