<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends BaseApiController
{
    public function index()
    {
        return $this->ok(Unit::forTenant()->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
        ]);
        $unit = Unit::create(['tenant_id' => $this->tenantId()] + $request->only('name','code'));
        return $this->ok($unit, 'Satuan ditambahkan.', 201);
    }

    public function update(Request $request, Unit $unit)
    {
        abort_if($unit->tenant_id !== $this->tenantId(), 403);
        $unit->update($request->only('name','code'));
        return $this->ok($unit, 'Satuan diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        abort_if($unit->tenant_id !== $this->tenantId(), 403);
        $unit->delete();
        return $this->ok(null, 'Satuan dihapus.');
    }
}
