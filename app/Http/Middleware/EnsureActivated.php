<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if ($user && $user->tenant && !$user->tenant->is_activated) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum aktif. Silakan beli paket aktivasi terlebih dahulu.',
                'error_code' => 'ACTIVATION_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
