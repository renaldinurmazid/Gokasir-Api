<?php

namespace App\Http\Controllers\Api;

use App\Models\Table;
use App\Models\TableSession;
use App\Models\TableOrder;
use App\Models\TableOrderItem;
use App\Models\Product;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicOrderController extends BaseApiController
{
    public function __construct(protected IPaymuService $ipaymu) {}

    /**
     * GET /public/menu/{tableCode}
     * Customer scan QR → ambil info meja + daftar menu
     */
    public function menu(string $tableCode)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with('store')
            ->firstOrFail();

        $products = Product::where('tenant_id', $table->tenant_id)
            ->where('is_active', true)
            ->with('category', 'unit')
            ->get()
            ->groupBy('category.name'); // grup per kategori untuk tampilan menu

        return $this->ok([
            'store' => [
                'name'    => $table->store->name,
                'logo'    => $table->store->logo,
                'address' => $table->store->address,
            ],
            'table' => [
                'id'       => $table->id,
                'name'     => $table->name,
                'code'     => $table->code,
                'location' => $table->location,
            ],
            'menu' => $products,
        ]);
    }

    /**
     * POST /public/order/{tableCode}/session
     * Buat sesi baru saat customer mulai order
     * Return: session_token (disimpan di browser/localStorage customer)
     */
    public function startSession(Request $request, string $tableCode)
    {
        $request->validate([
            'pax'            => 'nullable|integer|min:1',
            'customer_name'  => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:30',
        ]);

        $table = Table::where('code', $tableCode)->where('is_active', true)->firstOrFail();

        $session = TableSession::create([
            'tenant_id'      => $table->tenant_id,
            'store_id'       => $table->store_id,
            'table_id'       => $table->id,
            'session_token'  => Str::uuid()->toString(),
            'pax'            => $request->pax ?? 1,
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'status'         => 'active',
            'opened_at'      => now(),
        ]);

        return $this->ok([
            'session_token' => $session->session_token,
            'table_name'    => $table->name,
        ], 'Sesi dimulai.');
    }

    /**
     * POST /public/order/{tableCode}/place
     * Customer submit pesanan
     */
    public function placeOrder(Request $request, string $tableCode)
    {
        $request->validate([
            'session_token'       => 'required|string',
            'payment_type'        => 'required|in:cash,cashless',
            'payment_method'      => 'required_if:payment_type,cashless|string',
            'payment_channel'     => 'required_if:payment_type,cashless|string',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.qty'         => 'required|numeric|min:0.5',
            'items.*.notes'       => 'nullable|string',
        ]);

        $table = Table::where('code', $tableCode)->where('is_active', true)->firstOrFail();

        $session = TableSession::where('session_token', $request->session_token)
            ->where('table_id', $table->id)
            ->whereIn('status', ['active', 'ordered'])
            ->firstOrFail();

        // Ambil tax setting tenant
        $taxSetting = $table->store->tenant->getActiveTaxSetting();

        // Hitung total
        $subtotal = 0;
        $itemsData = [];

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('tenant_id', $table->tenant_id)
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

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        \DB::beginTransaction();
        try {
            $order = TableOrder::create([
                'tenant_id'      => $table->tenant_id,
                'store_id'       => $table->store_id,
                'table_id'       => $table->id,
                'session_id'     => $session->id,
                'order_number'   => $orderNumber,
                'status'         => 'pending',
                'payment_type'   => $request->payment_type,
                'payment_status' => 'unpaid',
                'payment_method' => $request->payment_method ?? null,
                'payment_channel'=> $request->payment_channel ?? null,
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'discount_amount'=> 0,
                'grand_total'    => $grandTotal,
                'notes'          => $request->notes,
                'created_at'     => now(),
            ]);

            foreach ($itemsData as $item) {
                TableOrderItem::create(array_merge(
                    ['table_order_id' => $order->id],
                    $item
                ));
            }

            // Update status sesi
            $session->update(['status' => 'ordered']);

            // Jika cashless → buat transaksi dengan iPaymu Direct API
            if ($request->payment_type === 'cashless') {
                $ipaymuResponse = $this->ipaymu->createPayment([
                    'tenant_id'       => $table->tenant_id,
                    'order_number'    => $orderNumber,
                    'amount'          => (int) $grandTotal,
                    'payment_method'  => $request->payment_method,
                    'payment_channel' => $request->payment_channel,
                    'buyer_name'      => $session->customer_name ?? 'Customer',
                    'buyer_email'     => 'order@gokasir.id',
                    'buyer_phone'     => $session->customer_phone ?? '-',
                    'description'     => "Order {$orderNumber} - {$table->name}",
                    'notify_url'      => config('app.url') . '/api/webhooks/ipaymu-order',
                ]);

                $responseData = $ipaymuResponse['Data'] ?? [];

                $order->update([
                    'ipaymu_trx_id'      => $responseData['TransactionId'] ?? null,
                    'payment_no'         => $responseData['PaymentNo'] ?? null,
                    'payment_name'       => $responseData['PaymentName'] ?? null,
                    'payment_fee'        => $responseData['Fee'] ?? 0,
                    'payment_url'        => $responseData['QrImage'] ?? $responseData['QrTemplate'] ?? $responseData['PaymentNo'] ?? null,
                    'payment_expired_at' => isset($responseData['Expired']) ? \Carbon\Carbon::parse($responseData['Expired']) : now()->addHours(1),
                    'payment_status'     => 'pending_payment',
                ]);
            }

            \DB::commit();

            return $this->ok([
                'order_number'   => $order->order_number,
                'table_name'     => $table->name,
                'grand_total'    => $order->grand_total,
                'payment_type'   => $order->payment_type,
                'payment_status' => $order->payment_status,
                'payment_no'     => $order->payment_no,
                'payment_name'   => $order->payment_name,
                'payment_fee'    => $order->payment_fee,
                'payment_url'    => $order->payment_url,
                'status'         => $order->status,
                'message'        => $request->payment_type === 'cash'
                    ? 'Pesanan diterima. Silakan bayar di kasir.'
                    : 'Pesanan diterima. Lanjutkan pembayaran.',
            ], 'Pesanan berhasil dikirim.', 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->fail('Gagal mengirim pesanan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /public/order/{tableCode}/status/{orderNumber}
     * Customer cek status pesanan
     */
    public function orderStatus(string $tableCode, string $orderNumber)
    {
        $table = Table::where('code', $tableCode)->firstOrFail();

        $order = TableOrder::where('order_number', $orderNumber)
            ->where('table_id', $table->id)
            ->with('items.product')
            ->firstOrFail();

        return $this->ok([
            'order_number'   => $order->order_number,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'grand_total'    => $order->grand_total,
            'payment_type'   => $order->payment_type,
            'payment_no'     => $order->payment_no,
            'payment_name'   => $order->payment_name,
            'payment_method' => $order->payment_method,
            'payment_channel'=> $order->payment_channel,
            'payment_fee'    => $order->payment_fee,
            'payment_url'    => $order->payment_url,
            'items'          => $order->items->map(fn($i) => [
                'product_name' => $i->product->name,
                'qty'          => $i->qty,
                'price'        => $i->price,
                'subtotal'     => $i->subtotal,
                'notes'        => $i->notes,
            ]),
            'message' => match($order->status) {
                'pending'    => 'Menunggu konfirmasi dari kasir.',
                'confirmed'  => 'Pesanan dikonfirmasi, sedang diproses.',
                'paid'       => 'Pesanan selesai. Terima kasih!',
                'cancelled'  => 'Pesanan dibatalkan.',
                default      => '-',
            },
        ]);
    }

    /**
     * GET /public/payment-methods
     * Customer get active payment methods (QRIS & Virtual Account) from iPaymu
     */
    public function paymentMethods()
    {
        try {
            $channels = $this->ipaymu->getPaymentChannels();

            $filterCategories = function ($categories) {
                return array_values(array_filter($categories, function ($cat) {
                    $code = strtolower($cat['Code'] ?? $cat['code'] ?? '');
                    return $code === 'va' || $code === 'qris';
                }));
            };

            if (isset($channels['Data']) && is_array($channels['Data'])) {
                $channels['Data'] = $filterCategories($channels['Data']);
            } elseif (isset($channels['data']) && is_array($channels['data'])) {
                $channels['data'] = $filterCategories($channels['data']);
            } elseif (is_array($channels)) {
                $channels = $filterCategories($channels);
            }

            return $this->ok($channels);
        } catch (\Exception $e) {
            return $this->fail('Gagal mengambil daftar metode pembayaran: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /public/order/{tableCode}/history
     * Retrieve order history using session_token from TableSession
     */
    public function orderHistory(Request $request, string $tableCode)
    {
        $sessionToken = $request->query('session_token');

        if (!$sessionToken) {
            return $this->fail('Session token wajib disertakan.', 400);
        }

        $table = Table::where('code', $tableCode)->where('is_active', true)->firstOrFail();

        $session = TableSession::where('session_token', $sessionToken)
            ->where('table_id', $table->id)
            ->first();

        if (!$session) {
            return $this->ok([], 'Sesi tidak ditemukan.');
        }

        $orders = TableOrder::where('session_id', $session->id)
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->ok($orders->map(fn($order) => [
            'order_number'   => $order->order_number,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'payment_type'   => $order->payment_type,
            'grand_total'    => $order->grand_total,
            'date'           => $order->created_at->toISOString(),
            'items'          => $order->items->map(fn($i) => [
                'product_name' => $i->product->name ?? 'Menu',
                'qty'          => $i->qty,
                'price'        => $i->price,
                'subtotal'     => $i->subtotal,
            ]),
        ]));
    }
}
