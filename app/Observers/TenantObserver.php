<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\TaxSetting;
use App\Models\TokenUsageLog;

class TenantObserver
{
    /**
     * Handle the Tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        // 1. Buat tax setting default (PPN 12%)
        TaxSetting::create([
            'tenant_id'   => $tenant->id,
            'tax_rate'    => 12.00,
            'tax_enabled' => true,
            'tax_name'    => 'PPN',
        ]);

        // 2. Catat log welcome token jika ada balance awal
        if ($tenant->token_balance > 0) {
            TokenUsageLog::create([
                'tenant_id'      => $tenant->id,
                'type'           => 'gift',
                'amount'         => $tenant->token_balance,
                'balance_before' => 0,
                'balance_after'  => $tenant->token_balance,
                'reference_type' => 'tenant',
                'reference_id'   => $tenant->id,
                'description'    => "Bonus {$tenant->token_balance} token untuk toko baru.",
                'created_at'     => now(),
            ]);
        }
    }
}
