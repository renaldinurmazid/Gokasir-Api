<?php

namespace App\Http\Controllers\Api;

use App\Jobs\SendMessageWhatsAppJobs;
use App\Jobs\SendTelegramMessageJobs;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Receivable;
use App\Models\Customer;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends BaseApiController
{
    public function __construct(protected TokenService $tokenService) {}

    // GET /api/sales
    public function index(Request $request)
    {
        $sales = Sale::forTenant()
            ->when($request->store_id,      fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from,          fn($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->to,            fn($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->with('customer', 'cashier', 'store')
            ->latest('transaction_date')
            ->paginate(20);

        return $this->ok($sales);
    }

    // POST /api/sales
    public function store(Request $request)
    {
        // ── PENGECEKAN TOKEN ──────────────────────────────────────────
        $tenant = auth()->user()->tenant;

        if (!$tenant->hasToken()) {
            $ownerPhone = $tenant->users()->where('role', 'owner')->value('phone');
            if ($ownerPhone) {
                dispatch(new SendMessageWhatsAppJobs(
                    "⚠️ Peringatan GoKasir!\nSaldo token *{$tenant->business_name}* tidak mencukupi. Kasir *" . auth()->user()->name . "* gagal melakukan transaksi. Segera top up token agar transaksi bisa dilanjutkan.",
                    $ownerPhone
                ));
            }

            dispatch(new SendTelegramMessageJobs(
                "⚠️ *Token Habis - Transaksi Gagal*\n\n"
                    . "🏢 *Bisnis:* " . $tenant->business_name . "\n"
                    . "👤 *Kasir:* " . auth()->user()->name . "\n"
                    . "📞 *No. HP Kasir:* " . auth()->user()->phone . "\n"
                    . "🏪 *Store ID:* " . $request->store_id . "\n"
                    . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s')
            ));

            return $this->fail(
                'Saldo token habis. Silakan topup token untuk melanjutkan transaksi.',
                402
            );
        }
        // ─────────────────────────────────────────────────────────────

        $request->validate([
            'store_id'       => 'required|exists:stores,id',
            'customer_id'    => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:cash,qris,transfer,debit,credit,tempo',
            'paid_amount'    => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount'     => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Hitung total dengan setting pajak
            $taxSetting = $tenant->getActiveTaxSetting();

            $subtotal = 0;
            foreach ($request->items as $item) {
                $disc     = $item['discount'] ?? 0;
                $subtotal += ($item['price'] * $item['qty']) - $disc;
            }
            $discount    = $request->discount_amount ?? 0;
            $afterDisc   = $subtotal - $discount;

            $tax = 0;
            if ($taxSetting->tax_enabled) {
                if ($taxSetting->tax_inclusive) {
                    $tax = round($afterDisc - ($afterDisc / (1 + ($taxSetting->tax_rate / 100))), 2);
                    $grandTotal = $afterDisc;
                } else {
                    $tax = round($afterDisc * ($taxSetting->tax_rate / 100), 2);
                    $grandTotal = $afterDisc + $tax;
                }
            } else {
                $grandTotal = $afterDisc;
            }

            $paidAmount  = $request->paid_amount;
            $change      = max(0, $paidAmount - $grandTotal);
            $payStatus   = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            if ($request->payment_method === 'tempo') {
                $payStatus = 'unpaid';
            }

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(10000, 99999);

            $sale = Sale::create([
                'tenant_id'      => $this->tenantId(),
                'store_id'       => $request->store_id,
                'invoice_number' => $invoiceNumber,
                'customer_id'    => $request->customer_id,
                'cashier_id'     => auth()->id(),
                'subtotal'       => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'     => $tax,
                'grand_total'    => $grandTotal,
                'paid_amount'    => $paidAmount,
                'change_amount'  => $change,
                'payment_method' => $request->payment_method,
                'payment_status' => $payStatus,
                'notes'          => $request->notes,
                'transaction_date' => now(),
                'created_at'     => now(),
            ]);

            // Simpan items & kurangi stok
            foreach ($request->items as $item) {
                $disc     = $item['discount'] ?? 0;
                $itemSub  = ($item['price'] * $item['qty']) - $disc;

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty'        => $item['qty'],
                    'price'      => $item['price'],
                    'discount'   => $disc,
                    'subtotal'   => $itemSub,
                ]);

                // Kurangi stok
                $stock = Stock::firstOrCreate(
                    ['store_id' => $request->store_id, 'product_id' => $item['product_id']],
                    ['tenant_id' => $this->tenantId(), 'qty' => 0]
                );
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
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            // ── POTONG TOKEN (setelah sale berhasil dibuat) ───────────
            $tokenDeducted = $this->tokenService->deductForSale(
                $tenant,
                $sale->id,
                $request->store_id,
                auth()->id()
            );

            if (!$tokenDeducted) {
                DB::rollBack();
                return $this->fail('Saldo token tidak mencukupi.', 402);
            }
            // ─────────────────────────────────────────────────────────

            // Buat piutang jika tempo/partial/unpaid
            if (in_array($payStatus, ['unpaid', 'partial']) && $request->customer_id) {
                $remaining = $grandTotal - $paidAmount;
                Receivable::create([
                    'tenant_id'       => $this->tenantId(),
                    'customer_id'     => $request->customer_id,
                    'sale_id'         => $sale->id,
                    'total_amount'    => $grandTotal,
                    'paid_amount'     => $paidAmount,
                    'remaining_amount' => $remaining,
                    'due_date'        => $request->due_date,
                    'status'          => $payStatus,
                    'created_at'      => now(),
                ]);

                // Update current_debt pelanggan
                Customer::where('id', $request->customer_id)
                    ->increment('current_debt', $remaining);
            }

            DB::commit();

            return $this->ok(
                array_merge(
                    $sale->load('items.product', 'customer', 'cashier', 'store')->toArray(),
                    ['token_balance_remaining' => $tenant->fresh()->token_balance]
                ),
                'Transaksi berhasil.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Transaksi gagal: ' . $e->getMessage(), 500);
        }
    }

    // GET /api/sales/today-overview?store_id=
    public function todayOverview(Request $request)
    {
        $tenantId = $this->tenantId();
        $storeId  = $request->store_id;
        $today    = now()->toDateString();

        $salesQuery = Sale::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', $today)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $totalRevenue      = (clone $salesQuery)->sum('grand_total');
        $totalTransactions = (clone $salesQuery)->count();

        $saleIds           = (clone $salesQuery)->pluck('id');
        $totalProductsSold = SaleItem::whereIn('sale_id', $saleIds)->sum('qty');

        return $this->ok([
            'total_revenue'           => (float) $totalRevenue,
            'total_revenue_formatted' => 'Rp' . number_format($totalRevenue, 0, ',', '.'),
            'total_products_sold'     => (int)    $totalProductsSold,
            'total_transactions'      => (int)    $totalTransactions,
        ]);
    }

    // GET /api/sales/{sale}
    public function show(Sale $sale)
    {
        abort_if($sale->tenant_id !== $this->tenantId(), 403);
        return $this->ok($sale->load('items.product.unit', 'customer', 'cashier', 'store', 'receivable'));
    }
}
