<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PlatformStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, PlatformStats $stats)
    {
        $days = (int) $request->query('range', 30);

        // Agregasi seluruh platform cukup berat → cache singkat per rentang grafik.
        $data = Cache::remember("admin:dashboard:{$days}", 60, fn () => $stats->dashboard($days));

        return Inertia::render('Admin/Dashboard', ['stats' => $data]);
    }
}
