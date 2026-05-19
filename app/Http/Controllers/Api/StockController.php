<?php

namespace App\Http\Controllers\Api;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends BaseApiController
{
    // GET /api/stocks?store_id=
    public function index(Request $request)
    {
        $storeId = $request->store_id ?? $this->storeId();

        $stocks = Stock::where('store_id', $storeId)
            ->where('tenant_id', $this->tenantId())
            ->with('product.category', 'product.unit', 'store')
            ->paginate(20);

        return $this->ok($stocks);
    }
}
