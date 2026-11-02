<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Tenant yang lama tidak aktif — target follow-up / retensi. */
class RetentionController extends Controller
{
    /** Aktivitas terakhir = transaksi terakhir; jika belum pernah transaksi, pakai tanggal daftar. */
    private const LAST_ACTIVITY = 'COALESCE((SELECT MAX(s.transaction_date) FROM sales s WHERE s.tenant_id = tenants.id), tenants.created_at)';

    public function index(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $days = in_array($days, [7, 14, 30, 60, 90], true) ? $days : 30;

        $cutoff = now()->subDays($days);

        $query = Tenant::query()->merchants()
            ->where('status', 'active')
            ->whereRaw(self::LAST_ACTIVITY . ' < ?', [$cutoff])
            ->with(['owner:id,tenant_id,name,phone,last_login'])
            ->withCount(['stores', 'products', 'sales'])
            ->withMax('sales', 'transaction_date')
            ->selectRaw(self::LAST_ACTIVITY . ' as last_activity_at');

        if ($q = trim((string) $request->query('q', ''))) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(fn ($w) => $w->where('business_name', 'like', $like)->orWhere('phone', 'like', $like));
        }

        $tenants = $query->orderBy('last_activity_at')->orderBy('id')->paginate(15)->withQueryString();

        $tenants->getCollection()->transform(function (Tenant $t) {
            $last = Carbon::parse($t->last_activity_at, config('app.timezone'));

            return [
                'id'             => $t->id,
                'business_name'  => $t->business_name,
                'phone'          => $t->owner?->phone ?? $t->phone,
                'owner_name'     => $t->owner?->name,
                'owner_last_login' => $t->owner?->last_login ? Carbon::parse($t->owner->last_login)->toIso8601String() : null,
                'is_activated'   => (bool) $t->is_activated,
                'token_balance'  => (int) $t->token_balance,
                'stores_count'   => $t->stores_count,
                'products_count' => $t->products_count,
                'sales_count'    => $t->sales_count,
                'never_sold'     => $t->sales_count === 0,
                'last_activity_at' => $last->toIso8601String(),
                'days_inactive'  => (int) $last->diffInDays(now()),
                'created_at'     => $t->created_at?->toIso8601String(),
            ];
        });

        // Ringkasan per ambang, satu query.
        $bucket = Tenant::query()->merchants()->where('status', 'active')
            ->selectRaw('
                COALESCE(SUM(' . self::LAST_ACTIVITY . ' < ?), 0) d14,
                COALESCE(SUM(' . self::LAST_ACTIVITY . ' < ?), 0) d30,
                COALESCE(SUM(' . self::LAST_ACTIVITY . ' < ?), 0) d60,
                COALESCE(SUM(' . self::LAST_ACTIVITY . ' < ?), 0) d90,
                COALESCE(SUM(NOT EXISTS (SELECT 1 FROM sales s WHERE s.tenant_id = tenants.id)), 0) never
            ', [now()->subDays(14), now()->subDays(30), now()->subDays(60), now()->subDays(90)])
            ->first();

        return Inertia::render('Admin/Retention', [
            'tenants' => $tenants,
            'days'    => $days,
            'q'       => (string) $request->query('q', ''),
            'buckets' => [
                14 => (int) $bucket->d14, 30 => (int) $bucket->d30,
                60 => (int) $bucket->d60, 90 => (int) $bucket->d90,
            ],
            'never_sold' => (int) $bucket->never,
        ]);
    }
}
