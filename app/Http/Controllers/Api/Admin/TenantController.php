<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends BaseApiController
{
    // PUT /api/admin/tenants/{tenant}/token-price
    public function setMitraTokenPrice(Request $request, Tenant $tenant)
    {
        $request->validate([
            'harga_token' => 'required|numeric|min:0',
            // 0 = cabut harga mitra, kembali ke harga master
        ]);

        $tenant->update(['harga_token' => $request->harga_token]);

        return $this->ok([
            'tenant_id'    => $tenant->id,
            'business_name'=> $tenant->business_name,
            'harga_token'  => $tenant->harga_token,
            'is_mitra'     => $tenant->hasMitraPrice(),
        ], $request->harga_token > 0
            ? "Harga mitra Rp {$request->harga_token}/token berhasil disimpan."
            : "Harga mitra dicabut. Tenant kembali ke harga master."
        );
    }
}
