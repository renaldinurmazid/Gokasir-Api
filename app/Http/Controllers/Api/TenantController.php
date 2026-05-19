<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class TenantController extends BaseApiController
{
    // GET /api/tenant
    public function show()
    {
        $tenant = auth()->user()->tenant;
        return $this->ok($tenant);
    }

    // PUT /api/tenant
    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $request->validate([
            'business_name' => 'required|string|max:150',
            'business_type' => 'nullable|string|max:100',
            'email'         => 'nullable|email|max:100',
            'phone'         => 'nullable|string|max:30',
        ]);

        $tenant->update($request->only('business_name', 'business_type', 'email', 'phone'));

        return $this->ok($tenant, 'Informasi tenant/usaha berhasil diperbarui.');
    }
}
