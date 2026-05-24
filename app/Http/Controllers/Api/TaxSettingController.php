<?php

namespace App\Http\Controllers\Api;

use App\Models\TaxSetting;
use Illuminate\Http\Request;

class TaxSettingController extends BaseApiController
{
    // GET /api/tax-settings
    public function show()
    {
        $tenant = auth()->user()->tenant;

        $setting = TaxSetting::firstOrCreate(
            ['tenant_id' => $this->tenantId()],
            [
                'tax_rate'      => $tenant->tax_rate ?? 12.00,
                'tax_enabled'   => true,
                'tax_name'      => 'PPN',
                'tax_inclusive' => false,
            ]
        );

        return $this->ok($setting);
    }

    // PUT /api/tax-settings  (owner only)
    public function update(Request $request)
    {
        $request->validate([
            'tax_rate'      => 'sometimes|numeric|min:0|max:100',
            'tax_enabled'   => 'sometimes|boolean',
            'tax_name'      => 'sometimes|string|max:50',
            'tax_inclusive' => 'sometimes|boolean',
        ]);

        $setting = TaxSetting::updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            array_merge(
                $request->only('tax_rate', 'tax_enabled', 'tax_name', 'tax_inclusive'),
                ['updated_by' => auth()->id()]
            )
        );

        // Sync ke kolom shortcut di tenants
        if ($request->has('tax_rate')) {
            auth()->user()->tenant->update(['tax_rate' => $request->tax_rate]);
        }

        return $this->ok($setting, 'Pengaturan pajak disimpan.');
    }
}
