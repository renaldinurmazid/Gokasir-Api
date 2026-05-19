<?php

namespace App\Http\Controllers\Api;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends BaseApiController
{
    // GET /api/stores
    public function index()
    {
        $stores = Store::forTenant()->withCount('users')->get();
        return $this->ok($stores);
    }

    // POST /api/stores  (owner only)
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'city'  => 'nullable|string|max:100',
            'logo'  => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name','address','city','province','postal_code','phone','email','receipt_footer');
        $data['tenant_id'] = $this->tenantId();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('stores', 'public');
        }

        $store = Store::create($data);

        return $this->ok($store, 'Toko berhasil dibuat.', 201);
    }

    // GET /api/stores/{store}
    public function show(Store $store)
    {
        $this->authorizeStore($store);
        return $this->ok($store->load('users'));
    }

    // PUT /api/stores/{store}
    public function update(Request $request, Store $store)
    {
        $this->authorizeStore($store);
        
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'city'  => 'nullable|string|max:100',
            'logo'  => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name','address','city','province','postal_code','phone','email','receipt_footer');

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            $data['logo'] = $request->file('logo')->store('stores', 'public');
        }

        $store->update($data);
        return $this->ok($store, 'Toko diperbarui.');
    }

    // DELETE /api/stores/{store}
    public function destroy(Store $store)
    {
        $this->authorizeStore($store);
        $store->delete();
        return $this->ok(null, 'Toko dihapus.');
    }

    private function authorizeStore(Store $store)
    {
        abort_if($store->tenant_id !== $this->tenantId(), 403, 'Akses ditolak.');
    }
}
