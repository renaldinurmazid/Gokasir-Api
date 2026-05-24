<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CashierController extends BaseApiController
{
    // GET /api/cashiers
    public function index(Request $request)
    {
        // Validasi store_id milik tenant jika disediakan
        if ($request->has('store_id')) {
            $storeId = $request->input('store_id');
            if ($storeId) {
                $storeExists = Store::forTenant()->where('id', $storeId)->exists();
                if (!$storeExists) {
                    return $this->fail('Toko tidak ditemukan atau bukan milik tenant Anda.', 422);
                }
            }
        }

        $cashiers = User::where('tenant_id', $this->tenantId())
            ->where('role', 'cashier')
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with('store')
            ->paginate(20);

        return $this->ok($cashiers);
    }

    // POST /api/cashiers
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|max:30|unique:users,phone',
            'password' => 'required|min:6',
            'store_id' => 'nullable|integer',
            'email'    => 'nullable|email|max:100',
            'status'   => 'nullable|in:0,1',
        ]);

        // Proteksi multi-tenant untuk store_id
        if ($request->store_id) {
            $storeExists = Store::forTenant()->where('id', $request->store_id)->exists();
            if (!$storeExists) {
                return $this->fail('Toko tidak ditemukan atau bukan milik tenant Anda.', 422);
            }
        }

        $cashier = User::create([
            'tenant_id' => $this->tenantId(),
            'store_id'  => $request->store_id,
            'role'      => 'cashier',
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'status'    => $request->input('status', 1),
        ]);

        return $this->ok($cashier, 'Kasir berhasil dibuat.', 201);
    }

    // GET /api/cashiers/{cashier}
    public function show(User $cashier)
    {
        $this->authorizeCashier($cashier);

        return $this->ok($cashier->load('store'));
    }

    // PUT /api/cashiers/{cashier}
    public function update(Request $request, User $cashier)
    {
        $this->authorizeCashier($cashier);

        $request->validate([
            'name'     => 'sometimes|string|max:100',
            'phone'    => 'sometimes|string|max:30|unique:users,phone,' . $cashier->id,
            'password' => 'sometimes|min:6',
            'store_id' => 'nullable|integer',
            'email'    => 'nullable|email|max:100',
            'status'   => 'sometimes|in:0,1',
        ]);

        // Proteksi multi-tenant untuk store_id
        if ($request->has('store_id') && $request->store_id) {
            $storeExists = Store::forTenant()->where('id', $request->store_id)->exists();
            if (!$storeExists) {
                return $this->fail('Toko tidak ditemukan atau bukan milik tenant Anda.', 422);
            }
        }

        $data = $request->only('name', 'email', 'phone', 'store_id', 'status');
        
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $cashier->update($data);

        return $this->ok($cashier, 'Data kasir berhasil diperbarui.');
    }

    // DELETE /api/cashiers/{cashier}
    public function destroy(User $cashier)
    {
        $this->authorizeCashier($cashier);

        $cashier->delete();

        return $this->ok(null, 'Kasir berhasil dihapus.');
    }

    // Helper untuk memvalidasi hak akses owner terhadap data kasir
    private function authorizeCashier(User $cashier)
    {
        abort_if($cashier->tenant_id !== $this->tenantId() || $cashier->role !== 'cashier', 403, 'Akses ditolak.');
    }
}
