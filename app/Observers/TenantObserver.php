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

        // 2. Catat log welcome token (balance sudah 500 dari default kolom DB)
        TokenUsageLog::create([
            'tenant_id'      => $tenant->id,
            'type'           => 'gift',
            'amount'         => 500,
            'balance_before' => 0,
            'balance_after'  => 500,
            'reference_type' => 'tenant',
            'reference_id'   => $tenant->id,
            'description'    => 'Bonus 500 token untuk toko baru.',
            'created_at'     => now(),
        ]);
    }
}
