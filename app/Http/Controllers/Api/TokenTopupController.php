<?php

namespace App\Http\Controllers\Api;

use App\Jobs\SendTelegramMessageJobs;
use App\Models\TokenTopup;
use App\Models\TokenPricing;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TokenTopupController extends BaseApiController
{
    public function __construct(protected IPaymuService $ipaymu) {}

    // GET /api/token-topups
    public function index(Request $request)
    {
        $topups = TokenTopup::where('tenant_id', $this->tenantId())
            ->with('pricing', 'user')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return $this->ok($topups);
    }

    // GET /api/token-balance
    public function balance()
    {
        $tenant = auth()->user()->tenant;
        return $this->ok([
            'token_balance'        => $tenant->token_balance,
            'token_lifetime_used'  => $tenant->token_lifetime_used,
            'token_lifetime_topup' => $tenant->token_lifetime_topup,
        ]);
    }

    // POST /api/token-topups
    public function store(Request $request)
    {
        $request->validate([
            'pricing_id'      => 'required|exists:token_pricing,id',
            'qty'             => 'nullable|integer|min:1|max:100000',
            'payment_method'  => 'required|string',
            'payment_channel' => 'required|string',
        ]);

        $pricing = TokenPricing::active()->findOrFail($request->pricing_id);
        $tenant  = auth()->user()->tenant;

        // ── Hitung harga efektif ──────────────────────────────────────────
        $hargaPerToken = $tenant->getEffectiveTokenPrice($pricing);

        if ($pricing->type === 'unit') {
            $qty         = $request->qty ?? 1;
            $tokenAmount = $pricing->token_amount * $qty;
            $totalPrice  = $hargaPerToken * $qty;          // ← pakai harga efektif
        } elseif ($pricing->type === 'activation') {
            $qty         = 1;
            $tokenAmount = $pricing->token_amount;
            $totalPrice  = $pricing->price;

            $user = auth()->user();
            if ($user->referred_by_id) {
                $referrer = \App\Models\User::find($user->referred_by_id);
                if ($referrer && $referrer->role === 'sales') {
                    $customPrice = \App\Models\SalesActivationPrice::where('sales_id', $referrer->id)
                        ->where('token_pricing_id', $pricing->id)
                        ->value('custom_price');
                    if ($customPrice !== null) {
                        $totalPrice = $customPrice;
                    }
                }
            }
        } else {
            // Paket: harga paket tidak terpengaruh harga mitra
            // (paket sudah punya harga bundel tersendiri)
            $qty         = 1;
            $tokenAmount = $pricing->token_amount;
            $totalPrice  = $pricing->price;               // ← tetap pakai harga paket
        }

        $orderNumber = 'TKN-' . strtoupper(Str::random(4)) . '-' . time();

        $topup = TokenTopup::create([
            'tenant_id'      => $this->tenantId(),
            'user_id'        => auth()->id(),
            'pricing_id'     => $pricing->id,
            'order_number'   => $orderNumber,
            'token_amount'   => $tokenAmount,
            'price'          => $totalPrice,
            'qty'            => $qty,
            'payment_method' => $request->payment_method,
            'payment_channel' => $request->payment_channel,
            'status'         => 'pending',
            'expired_at'     => now()->addHours(24),
        ]);

        $buyerPhone = auth()->user()->phone ?? '081284725661';
        $buyerPhone = preg_replace('/[^0-9+]/', '', $buyerPhone);
        $buyerPhone = preg_replace('/^(?:\+62|0)/', '62', $buyerPhone);

        try {
            $ipaymuResponse = $this->ipaymu->createPayment([
                'tenant_id'       => $this->tenantId(),
                'order_number'    => $orderNumber,
                'amount'          => (int) $totalPrice,
                'payment_method'  => $topup->payment_method,
                'payment_channel' => $topup->payment_channel,
                'buyer_name'      => auth()->user()->name,
                'buyer_email'     => auth()->user()->email ?? 'customer@gokasir.net',
                'buyer_phone'     => $buyerPhone,
                'description'     => "Topup {$tokenAmount} Token GoKasir",
                'notify_url'      => config('app.url') . '/api/webhooks/ipaymu',
            ]);

            $responseData = $ipaymuResponse['Data'] ?? [];

            $topup->update([
                'ipaymu_trx_id'       => $responseData['TransactionId'] ?? null,
                'ipaymu_reference'    => $responseData['ReferenceId'] ?? null,
                'payment_no'          => $responseData['PaymentNo'] ?? null,
                'payment_name'        => $responseData['PaymentName'] ?? null,
                'payment_fee'         => 0,
                'payment_url'         => $responseData['Url'] ?? $responseData['QrImage'] ?? $responseData['QrTemplate'] ?? $responseData['PaymentNo'] ?? null,
                'expired_at'          => isset($responseData['Expired']) ? \Carbon\Carbon::parse($responseData['Expired']) : now()->addHours(24),
                'ipaymu_raw_response' => json_encode($ipaymuResponse),
            ]);
        } catch (\Exception $e) {
            $topup->update(['status' => 'failed']);
            $this->notifyTelegram(
                "⚠️ *Topup Token Gagal Dibuat*\n\n"
                    . "🏢 *Bisnis:* " . SendTelegramMessageJobs::escape($tenant->business_name) . "\n"
                    . "👤 *User:* " . SendTelegramMessageJobs::escape(auth()->user()->name) . "\n"
                    . "🧾 *No. Order:* " . SendTelegramMessageJobs::escape($orderNumber) . "\n"
                    . "🪙 *Token:* " . number_format($tokenAmount, 0, ',', '.') . "\n"
                    . "💵 *Harga:* Rp" . number_format($totalPrice, 0, ',', '.') . "\n"
                    . "📝 *Alasan:* " . SendTelegramMessageJobs::escape($e->getMessage()) . "\n"
                    . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s')
            );
            return $this->fail('Gagal membuat pembayaran: ' . $e->getMessage(), 500);
        }

        $this->notifyTelegram(
            "🛒 *Order Topup Token Baru*\n\n"
                . "🏢 *Bisnis:* " . SendTelegramMessageJobs::escape($tenant->business_name) . "\n"
                . "👤 *User:* " . SendTelegramMessageJobs::escape(auth()->user()->name) . "\n"
                . "📞 *No. HP:* " . auth()->user()->phone . "\n"
                . "🧾 *No. Order:* " . SendTelegramMessageJobs::escape($topup->order_number) . "\n"
                . "📦 *Paket:* " . SendTelegramMessageJobs::escape($pricing->name) . "\n"
                . "🪙 *Token:* " . number_format($tokenAmount, 0, ',', '.') . "\n"
                . "💵 *Harga:* Rp" . number_format($totalPrice, 0, ',', '.') . "\n"
                . "💳 *Metode:* " . SendTelegramMessageJobs::escape($topup->payment_method . ' / ' . $topup->payment_channel) . "\n"
                . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s')
        );

        return $this->ok([
            'order_number'    => $topup->order_number,
            'token_amount'    => $topup->token_amount,
            'price'           => $topup->price,
            'payment_no'      => $topup->payment_no,
            'payment_name'    => $topup->payment_name,
            'payment_fee'     => 0,
            'payment_url'     => $topup->payment_url,
            'payment_method'  => $topup->payment_method,
            'payment_channel' => $topup->payment_channel,
            'expired_at'      => $topup->expired_at,
            'status'          => $topup->status,
        ], 'Order topup dibuat. Lanjutkan pembayaran.', 201);
    }

    // GET /api/token-topups/payment-channels
    public function paymentChannels()
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

    // GET /api/token-topups/{orderNumber}/check
    public function checkStatus(string $orderNumber)
    {
        $topup = TokenTopup::where('tenant_id', $this->tenantId())
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return $this->ok([
            'order_number' => $topup->order_number,
            'status'       => $topup->status,
            'token_amount' => $topup->token_amount,
            'paid_at'      => $topup->paid_at,
        ]);
    }

    private function notifyTelegram(string $text): void
    {
        try {
            dispatch(new SendTelegramMessageJobs($text));
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }
    }
}
