<?php

namespace App\Http\Controllers\Api;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends BaseApiController
{
    // GET /api/receivables
    public function index(Request $request)
    {
        $receivables = Receivable::where('tenant_id', $this->tenantId())
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status,      fn($q) => $q->where('status', $request->status))
            ->with('customer', 'sale')
            ->latest('created_at')
            ->paginate(20);

        return $this->ok($receivables);
    }

    // GET /api/receivables/{receivable}
    public function show(Receivable $receivable)
    {
        abort_if($receivable->tenant_id !== $this->tenantId(), 403);
        return $this->ok($receivable->load('payments.createdBy', 'customer', 'sale.items.product'));
    }

    // POST /api/receivables/{receivable}/pay  - catat pembayaran piutang
    public function pay(Request $request, Receivable $receivable)
    {
        abort_if($receivable->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'notes'          => 'nullable|string',
        ]);

        if ($request->amount > $receivable->remaining_amount) {
            return $this->fail('Jumlah bayar melebihi sisa piutang.', 422);
        }

        DB::beginTransaction();
        try {
            ReceivablePayment::create([
                'receivable_id'  => $receivable->id,
                'payment_date'   => now(),
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
            ]);

            $newPaid      = $receivable->paid_amount + $request->amount;
            $newRemaining = $receivable->total_amount - $newPaid;
            $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

            $receivable->update([
                'paid_amount'      => $newPaid,
                'remaining_amount' => max(0, $newRemaining),
                'status'           => $newStatus,
            ]);

            // Update current_debt pelanggan
            Customer::where('id', $receivable->customer_id)
                ->decrement('current_debt', $request->amount);

            DB::commit();
            return $this->ok($receivable->fresh(), 'Pembayaran piutang dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat pembayaran.', 500);
        }
    }

    // POST /api/receivables/overdue-check  - tandai overdue
    public function markOverdue()
    {
        $updated = Receivable::where('tenant_id', $this->tenantId())
            ->whereIn('status', ['unpaid','partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        return $this->ok(['updated' => $updated], 'Status piutang overdue diperbarui.');
    }
}
