<?php

namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        // Berikan komisi Rp 20 ke sales per transaksi
        $owner = \App\Models\User::where('tenant_id', $sale->tenant_id)->where('role', 'owner')->first();
        if ($owner && $owner->referred_by_id) {
            $salesUser = \App\Models\User::find($owner->referred_by_id);
            if ($salesUser && $salesUser->role === 'sales') {
                $commission = 20; // Rp 20 per transaksi
                $salesUser->addSalesWalletCommission(
                    $commission,
                    'transaction_bonus',
                    $sale,
                    "Bonus transaksi {$sale->invoice_number} dari tenant {$owner->tenant->business_name}"
                );
            }
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "restored" event.
     */
    public function restored(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "force deleted" event.
     */
    public function forceDeleted(Sale $sale): void
    {
        //
    }
}
