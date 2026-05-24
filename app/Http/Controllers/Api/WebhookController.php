<?php

namespace App\Http\Controllers\Api;

use App\Models\TokenTopup;
use App\Services\TokenService;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
}
