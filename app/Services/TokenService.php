<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TokenUsageLog;
use Illuminate\Support\Facades\DB;

class TokenService
{
    /**
     * Kurangi token untuk transaksi penjualan.
     * Return false jika token habis.
     */
    public function deductForSale(Tenant $tenant, int $saleId, int $storeId, int $userId): bool
    {
        return DB::transaction(function () use ($tenant, $saleId, $storeId, $userId) {
            $balanceBefore = $tenant->token_balance;

            if (!$tenant->deductToken(1)) {
                return false;
            }

            TokenUsageLog::create([
                'tenant_id'      => $tenant->id,
                'store_id'       => $storeId,
                'user_id'        => $userId,
                'type'           => 'deduct',
                'amount'         => -1,
                'balance_before' => $balanceBefore,
                'balance_after'  => $tenant->token_balance,
                'reference_type' => 'sale',
                'reference_id'   => $saleId,
                'description'    => 'Token digunakan untuk transaksi #' . $saleId,
                'created_at'     => now(),
            ]);

            return true;
        });
    }

    /**
     * Tambah token setelah topup berhasil.
     */
    public function creditFromTopup(Tenant $tenant, int $topupId, int $tokenAmount, int $userId): void
    {
        DB::transaction(function () use ($tenant, $topupId, $tokenAmount, $userId) {
            $balanceBefore = $tenant->token_balance;
            $tenant->addToken($tokenAmount);

            TokenUsageLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => $userId,
                'type'           => 'topup',
                'amount'         => $tokenAmount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $tenant->token_balance,
                'reference_type' => 'topup',
                'reference_id'   => $topupId,
                'description'    => "Topup {$tokenAmount} token berhasil.",
                'created_at'     => now(),
            ]);
        });
    }

    /**
     * Token gratis saat tenant baru dibuat.
     */
    public function giftWelcomeToken(Tenant $tenant, int $amount = 500): void
    {
        $balanceBefore = $tenant->token_balance;
        $tenant->addToken($amount);

        TokenUsageLog::create([
            'tenant_id'      => $tenant->id,
            'type'           => 'gift',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $tenant->token_balance,
            'reference_type' => 'tenant',
            'reference_id'   => $tenant->id,
            'description'    => "Bonus {$amount} token untuk toko baru.",
            'created_at'     => now(),
        ]);
    }
}
