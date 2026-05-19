<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $tenant = auth()->user()->tenant;

            if (!$tenant || $tenant->status !== 'active') {
                return response()->json(['message' => 'Tenant tidak aktif atau suspended.'], 403);
            }

            // Simpan di request agar bisa diakses di controller
            $request->merge(['_tenant' => $tenant]);
        }

        return $next($request);
    }
}
