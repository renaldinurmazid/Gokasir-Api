<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseApiController extends Controller
{
    protected function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    protected function storeId(): ?int
    {
        return auth()->user()->store_id;
    }

    protected function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function fail(string $message, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }
}
