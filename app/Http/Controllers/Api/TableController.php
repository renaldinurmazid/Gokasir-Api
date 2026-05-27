<?php

namespace App\Http\Controllers\Api;

use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableController extends BaseApiController
{
    // GET /api/tables?store_id=
    public function index(Request $request)
    {
        $tables = Table::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->withCount(['orders as pending_orders_count' => fn($q) =>
                $q->where('status', 'pending')
            ])
            ->with('activeSession')
            ->get();

        return $this->ok($tables);
    }

    // POST /api/tables
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name'     => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:100',
        ]);

        $code  = 'TBL-' . strtoupper(Str::random(8));
        $qrUrl = url('order/' . $code);

        $table = Table::create(array_merge(
            $request->only('store_id', 'name', 'capacity', 'location'),
            [
                'tenant_id' => $this->tenantId(),
                'code'      => $code,
                'qr_url'    => $qrUrl,
                'is_active' => true,
            ]
        ));

        return $this->ok($table, 'Meja berhasil ditambahkan.', 201);
    }

    // GET /api/tables/{table}
    public function show(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);
        return $this->ok($table->load('activeSession'));
    }

    // PUT /api/tables/{table}
    public function update(Request $request, Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);
        
        $request->validate([
            'name'      => 'sometimes|required|string|max:50',
            'capacity'  => 'sometimes|nullable|integer|min:1',
            'location'  => 'sometimes|nullable|string|max:100',
            'is_active' => 'sometimes|required|boolean',
        ]);

        $table->update($request->only('name', 'capacity', 'location', 'is_active'));
        
        return $this->ok($table, 'Meja diperbarui.');
    }

    // DELETE /api/tables/{table}
    public function destroy(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);
        $table->delete();
        return $this->ok(null, 'Meja dihapus.');
    }

    // GET /api/tables/{table}/orders — semua pesanan aktif di meja ini
    public function activeOrders(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);

        $orders = $table->orders()
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('items.product', 'session')
            ->latest()
            ->get();

        return $this->ok([
            'table'          => $table,
            'active_session' => $table->activeSession,
            'orders'         => $orders,
        ]);
    }

    // POST /api/tables/{table}/regenerate-qr — buat ulang QR
    public function regenerateQr(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);

        $code  = 'TBL-' . strtoupper(Str::random(8));
        $qrUrl = url('order/' . $code);

        $table->update([
            'code'     => $code,
            'qr_url'   => $qrUrl,
            'qr_image' => null
        ]);

        return $this->ok($table, 'QR code berhasil diperbarui.');
    }
}
