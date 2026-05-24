<?php

namespace App\Http\Controllers\Api;

use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends BaseApiController
{
    // GET /api/payables?store_id=&supplier_id=&status=
    public function index(Request $request)
    {
        $payables = Payable::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->supplier_id,fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status,     fn($q) => $q->where('status', $request->status))
            ->with('supplier', 'purchase')
            ->latest('created_at')
            ->paginate(20);

        return $this->ok($payables);
    }

    // GET /api/payables/{payable}
    public function show(Payable $payable)
    {
        abort_if($payable->tenant_id !== $this->tenantId(), 403);
        return $this->ok($payable->load('payments.createdBy', 'supplier', 'purchase.items.product'));
    }

    // POST /api/payables/{payable}/pay
    public function pay(Request $request, Payable $payable)
    {
        abort_if($payable->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'notes'          => 'nullable|string',
        ]);

        if ($request->amount > $payable->remaining_amount) {
            return $this->fail('Jumlah bayar melebihi sisa hutang.', 422);
        }

        DB::beginTransaction();
        try {
            PayablePayment::create([
                'payable_id'     => $payable->id,
                'payment_date'   => now(),
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
            ]);

            $newPaid      = $payable->paid_amount + $request->amount;
            $newRemaining = max(0, $payable->total_amount - $newPaid);
            $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

            $payable->update([
                'paid_amount'      => $newPaid,
                'remaining_amount' => $newRemaining,
                'status'           => $newStatus,
            ]);

            Supplier::where('id', $payable->supplier_id)
                ->decrement('current_debt', $request->amount);

            DB::commit();
            return $this->ok($payable->fresh(), 'Pembayaran hutang dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat pembayaran.', 500);
        }
    }

    // POST /api/payables/overdue-check
    public function markOverdue()
    {
        $updated = Payable::where('tenant_id', $this->tenantId())
            ->whereIn('status', ['unpaid','partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        return $this->ok(['updated' => $updated], 'Status hutang overdue diperbarui.');
    }
}
