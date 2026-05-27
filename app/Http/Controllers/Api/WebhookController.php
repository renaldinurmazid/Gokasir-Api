<?php

namespace App\Http\Controllers\Api;

use App\Models\TokenTopup;
use App\Models\TableOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\TokenService;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends BaseApiController
{
    public function __construct(
        protected TokenService  $tokenService,
        protected IPaymuService $ipaymu,
    ) {}

    /**
     * POST /api/webhooks/ipaymu
     */
    public function ipaymu(Request $request)
    {
        Log::channel('ipaymu')->info('Webhook received', $request->all() ?: []);

        // Verifikasi signature iPaymu
        if (!$this->ipaymu->verifySignature($request)) {
            Log::channel('ipaymu')->warning('Invalid signature', $request->all() ?: []);
            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $trxId       = $request->input('trx_id');
        $sid         = $request->input('sid');
        $referenceId = $request->input('reference_id');
        $status      = $request->input('status');
        $statusCode  = $request->input('status_code');

        $topup = TokenTopup::where('order_number', $referenceId)
            ->orWhere('ipaymu_trx_id', $sid)
            ->first();

        if (!$topup) {
            Log::channel('ipaymu')->error('Topup not found', ['trx_id' => $trxId, 'sid' => $sid, 'reference_id' => $referenceId]);
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($topup->isPaid()) {
            return response()->json(['status' => 'already_processed']);
        }

        if ($statusCode == 1 || strtolower($status) === 'berhasil') {
            $tenant = $topup->tenant;
            $balanceBefore = $tenant->token_balance;

            $topup->update([
                'status'          => 'paid',
                'paid_at'         => now(),
                'balance_before'  => $balanceBefore,
                'balance_after'   => $balanceBefore + $topup->token_amount,
                'payment_method'  => $request->input('via'),
                'payment_channel' => $request->input('channel'),
            ]);

            $this->tokenService->creditFromTopup(
                $tenant,
                $topup->id,
                $topup->token_amount,
                $topup->user_id
            );

            Log::channel('ipaymu')->info('Topup SUCCESS', [
                'order'         => $topup->order_number,
                'token_amount'  => $topup->token_amount,
                'tenant_id'     => $tenant->id,
            ]);

        } else {
            if ($statusCode == 3 || strtolower($status) === 'expired' || strtolower($status) === 'kadaluarsa') {
                $topup->update(['status' => 'expired']);
            } elseif ($statusCode == -1 || $statusCode == 2 || strtolower($status) === 'gagal' || strtolower($status) === 'failed') {
                $topup->update(['status' => 'failed']);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * POST /api/webhooks/ipaymu-order
     * Notifikasi pembayaran cashless dari customer via web order
     */
    public function ipaymuOrder(Request $request)
    {
        Log::channel('ipaymu')->info('Order webhook received', $request->all() ?: []);

        if (!$this->ipaymu->verifySignature($request)) {
            Log::channel('ipaymu')->warning('Order webhook signature invalid', $request->all() ?: []);
            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $trxId       = $request->input('trx_id');
        $sid         = $request->input('sid');  // order_number
        $referenceId = $request->input('reference_id');
        $status      = $request->input('status');
        $statusCode  = $request->input('status_code');

        $order = TableOrder::where('order_number', $referenceId)
            ->orWhere('ipaymu_trx_id', $sid)
            ->first();

        if (!$order) {
            Log::channel('ipaymu')->error('Order not found', ['trx_id' => $trxId, 'sid' => $sid, 'reference_id' => $referenceId]);
            return response()->json(['status' => 'skipped']);
        }

        if ($order->isPaid()) {
            return response()->json(['status' => 'already_processed']);
        }

        if ($statusCode == 1 || strtolower($status) === 'berhasil') {
            $tenant = $order->store->tenant;

            if (!$tenant->hasToken()) {
                Log::channel('ipaymu')->warning('Token habis saat webhook order', ['order' => $sid]);
                return response()->json(['status' => 'token_empty'], 200);
            }

            DB::beginTransaction();
            try {
                $invoiceNum = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

                $sale = Sale::create([
                    'tenant_id'       => $order->tenant_id,
                    'store_id'        => $order->store_id,
                    'invoice_number'  => $invoiceNum,
                    'cashier_id'      => $order->confirmed_by ?? 1, // Sistem bot / Kasir pengkonfirmasi
                    'subtotal'        => $order->subtotal,
                    'discount_amount' => $order->discount_amount,
                    'tax_amount'      => $order->tax_amount,
                    'grand_total'     => $order->grand_total,
                    'paid_amount'     => $order->grand_total,
                    'change_amount'   => 0,
                    'payment_method'  => $order->payment_method ?? 'qris',
                    'payment_status'  => 'paid',
                    'notes'           => 'Cashless order dari Meja: ' . $order->table->name . ' | ' . $order->order_number,
                    'transaction_date'=> now(),
                    'created_at'      => now(),
                ]);

                foreach ($order->items()->where('status', 'confirmed')->get() as $item) {
                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $item->product_id,
                        'qty'        => $item->qty,
                        'price'      => $item->price,
                        'discount'   => $item->discount,
                        'subtotal'   => $item->subtotal,
                    ]);

                    $stock = Stock::firstOrCreate(
                        ['store_id' => $order->store_id, 'product_id' => $item->product_id],
                        ['tenant_id' => $order->tenant_id, 'qty' => 0]
                    );
                    $stockBefore = $stock->qty;
                    $stock->decrement('qty', $item->qty);
                    $stock->refresh();

                    StockMovement::create([
                        'tenant_id'      => $order->tenant_id,
                        'store_id'       => $order->store_id,
                        'product_id'     => $item->product_id,
                        'type'           => 'out',
                        'qty'            => $item->qty,
                        'stock_before'   => $stockBefore,
                        'stock_after'    => $stock->qty,
                        'reference_type' => 'sale',
                        'reference_id'   => $sale->id,
                        'created_at'     => now(),
                    ]);
                }

                $order->update([
                    'status'         => 'paid',
                    'payment_status' => 'paid',
                    'sale_id'        => $sale->id,
                ]);

                $order->session->update(['status' => 'paid', 'closed_at' => now()]);

                // Potong token
                $this->tokenService->deductForSale(
                    $tenant,
                    $sale->id,
                    $order->store_id,
                    $order->confirmed_by ?? 1
                );

                DB::commit();
                Log::channel('ipaymu')->info('Order payment SUCCESS', ['order' => $order->order_number, 'sale_id' => $sale->id]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('ipaymu')->error('Gagal proses order webhook', ['error' => $e->getMessage()]);
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
