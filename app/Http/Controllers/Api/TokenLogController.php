<?php

namespace App\Http\Controllers\Api;

use App\Models\TokenUsageLog;
use Illuminate\Http\Request;

class TokenLogController extends BaseApiController
{
    // GET /api/token-logs
    public function index(Request $request)
    {
        $logs = TokenUsageLog::where('tenant_id', $this->tenantId())
            ->when($request->type,  fn($q) => $q->where('type', $request->type))
            ->when($request->from,  fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,    fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->with('store', 'user')
            ->latest('created_at')
            ->paginate(30);

        return $this->ok($logs);
    }
}
