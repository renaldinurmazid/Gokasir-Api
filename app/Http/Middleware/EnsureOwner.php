<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOwner
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->role !== 'owner') {
            return response()->json(['message' => 'Akses ditolak. Hanya owner.'], 403);
        }
        return $next($request);
    }
}
