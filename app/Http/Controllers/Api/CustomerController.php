<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    public function index(Request $request)
    {
        $customers = Customer::forTenant()
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
            )
            ->paginate(20);

        return $this->ok($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create(array_merge(
            $request->only('name','phone','address','credit_limit'),
            ['tenant_id' => $this->tenantId()]
        ));

        return $this->ok($customer, 'Pelanggan ditambahkan.', 201);
    }

    // GET /api/customers/{customer}
    public function show(Customer $customer)
    {
        abort_if($customer->tenant_id !== $this->tenantId(), 403);
        return $this->ok($customer->load('receivables.sale'));
    }

    // PUT /api/customers/{customer}
    public function update(Request $request, Customer $customer)
    {
        abort_if($customer->tenant_id !== $this->tenantId(), 403);
        $customer->update($request->only('name','phone','address','credit_limit'));
        return $this->ok($customer, 'Pelanggan diperbarui.');
    }

    // DELETE /api/customers/{customer}
    public function destroy(Customer $customer)
    {
        abort_if($customer->tenant_id !== $this->tenantId(), 403);
        $customer->delete();
        return $this->ok(null, 'Pelanggan dihapus.');
    }
}
