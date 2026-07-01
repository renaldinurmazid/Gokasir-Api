<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesWalletController extends \App\Http\Controllers\Api\BaseApiController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'sales') {
            return $this->fail('Unauthorized', 403);
        }

        $wallet = $user->salesWallet()->firstOrCreate(
            ['sales_id' => $user->id],
            ['balance' => 0]
        );

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->ok([
            'balance'      => $wallet->balance,
            'transactions' => $transactions->items(),
            'pagination'   => [
                'total'        => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
            ]
        ], 'Data dompet sales berhasil diambil.');
    }
}
