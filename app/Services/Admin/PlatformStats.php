<?php

namespace App\Services\Admin;

use App\Models\Sale;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\TokenTopup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Statistik level platform untuk dashboard admin.
 *
 * Semua kolom tanggal disimpan memakai config('app.timezone'). Batas hari/minggu/bulan
 * dihitung di zona waktu admin (config('admin.timezone')) lalu dikonversi balik ke zona
 * waktu penyimpanan, sehingga "hari ini" sesuai jam bisnis (WIB), bukan jam UTC.
 */
class PlatformStats
{
    private string $tz;
    private string $appTz;
    private CarbonImmutable $now;

    public function __construct()
    {
        $this->tz    = config('admin.timezone');
        $this->appTz = config('app.timezone');
        $this->now   = CarbonImmutable::now($this->tz);
    }

    public function dashboard(int $days = 30): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        return [
            'totals'    => $this->totals(),
            'tenants'   => $this->tenantHealth(),
            'sales'     => $this->salesPeriods(),
            'topups'    => $this->topupPeriods(),
            'daily'     => $this->dailySeries($days),
            'monthly'   => $this->monthlyGrowth(12),
            'range'     => $days,
            'generated' => $this->now->toIso8601String(),
        ];
    }

    // ── Total & pertumbuhan ──────────────────────────────────────────────

    private function totals(): array
    {
        $monthStart     = $this->toApp($this->now->startOfMonth());
        $lastMonthStart = $this->toApp($this->now->subMonthNoOverflow()->startOfMonth());

        $tenants = fn () => Tenant::query()->merchants();
        $stores  = fn () => Store::query()->whereHas('tenant', fn ($q) => $q->merchants());
        $users   = fn () => User::query()->whereIn('role', ['owner', 'cashier'])
            ->whereHas('tenant', fn ($q) => $q->merchants());

        $build = function (callable $q) use ($monthStart, $lastMonthStart) {
            $thisMonth = $q()->where('created_at', '>=', $monthStart)->count();
            $lastMonth = $q()->where('created_at', '>=', $lastMonthStart)
                ->where('created_at', '<', $monthStart)->count();

            return [
                'total'      => $q()->count(),
                'this_month' => $thisMonth,
                'last_month' => $lastMonth,
                // null = tidak ada pembanding (bulan lalu 0)
                'growth_pct' => $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1) : null,
            ];
        };

        return [
            'tenants'        => $build($tenants),
            'stores'         => $build($stores),
            'users'          => $build($users),
            'sales_partners' => User::where('role', 'sales')->count(),
        ];
    }

    /**
     * Kesehatan tenant — empat kelompok yang saling lepas dan jumlahnya = total:
     *  - suspended : status suspended
     *  - active    : ada transaksi dalam N hari terakhir
     *  - inactive  : tanpa transaksi N hari DAN sudah terdaftar lebih dari N hari (target retensi)
     *  - fresh     : terdaftar < N hari yang lalu dan belum bertransaksi
     * Definisi `inactive` sama persis dengan halaman Retensi (ambang 30 hari).
     */
    private function tenantHealth(): array
    {
        $activeDays = (int) config('admin.active_days', 30);
        $cutoff     = $this->toApp($this->now->subDays($activeDays));

        $total     = Tenant::merchants()->count();
        $suspended = Tenant::merchants()->where('status', 'suspended')->count();
        $active    = Tenant::merchants()
            ->where('status', '!=', 'suspended')
            ->whereExists(function ($q) use ($cutoff) {
                $q->selectRaw('1')->from('sales')
                    ->whereColumn('sales.tenant_id', 'tenants.id')
                    ->where('sales.transaction_date', '>=', $cutoff);
            })
            ->count();
        $inactive = Tenant::merchants()
            ->where('status', '!=', 'suspended')
            ->whereRaw(Tenant::LAST_ACTIVITY_SQL . ' < ?', [$cutoff])
            ->count();

        return [
            'active_days' => $activeDays,
            'total'       => $total,
            'new_30d'     => Tenant::merchants()->where('created_at', '>=', $this->toApp($this->now->subDays(30)))->count(),
            'active'      => $active,
            'inactive'    => $inactive,
            'suspended'   => $suspended,
            'fresh'       => max(0, $total - $active - $inactive - $suspended),
        ];
    }

    /** Angka kecil untuk badge sidebar (di-cache oleh pemanggil). */
    public function navCounts(): array
    {
        $h = $this->tenantHealth();

        return ['tenants' => $h['total'], 'inactive' => $h['inactive']];
    }

    // ── Transaksi & omzet ────────────────────────────────────────────────

    private function salesPeriods(): array
    {
        $today = $this->toApp($this->now->startOfDay());
        $week  = $this->toApp($this->now->startOfWeek());
        $month = $this->toApp($this->now->startOfMonth());
        $from  = min($today, $week, $month);

        $row = DB::table('sales')
            ->where('transaction_date', '>=', $from)
            ->selectRaw('
                COALESCE(SUM(transaction_date >= ?), 0) as today_count,
                COALESCE(SUM(CASE WHEN transaction_date >= ? THEN grand_total END), 0) as today_sum,
                COALESCE(SUM(transaction_date >= ?), 0) as week_count,
                COALESCE(SUM(CASE WHEN transaction_date >= ? THEN grand_total END), 0) as week_sum,
                COALESCE(SUM(transaction_date >= ?), 0) as month_count,
                COALESCE(SUM(CASE WHEN transaction_date >= ? THEN grand_total END), 0) as month_sum
            ', [$today, $today, $week, $week, $month, $month])
            ->first();

        return [
            'today' => ['count' => (int) $row->today_count, 'revenue' => (float) $row->today_sum],
            'week'  => ['count' => (int) $row->week_count,  'revenue' => (float) $row->week_sum],
            'month' => ['count' => (int) $row->month_count, 'revenue' => (float) $row->month_sum],
        ];
    }

    private function topupPeriods(): array
    {
        $today = $this->toApp($this->now->startOfDay());
        $week  = $this->toApp($this->now->startOfWeek());
        $month = $this->toApp($this->now->startOfMonth());

        $sum = function (?Carbon $from) {
            $q = TokenTopup::query()->where('status', 'paid');
            if ($from) {
                $q->where('paid_at', '>=', $from);
            }
            $r = $q->selectRaw('COUNT(*) c, COALESCE(SUM(price),0) s, COALESCE(SUM(token_amount),0) t')->first();

            return ['count' => (int) $r->c, 'revenue' => (float) $r->s, 'tokens' => (int) $r->t];
        };

        return [
            'today' => $sum($today),
            'week'  => $sum($week),
            'month' => $sum($month),
            'all'   => $sum(null),
            'pending' => TokenTopup::where('status', 'pending')->count(),
        ];
    }

    // ── Deret waktu ──────────────────────────────────────────────────────

    /** Transaksi & omzet harian + top-up harian, N hari terakhir (hari kosong diisi 0). */
    private function dailySeries(int $days): array
    {
        $start = $this->now->subDays($days - 1)->startOfDay();
        $from  = $this->toApp($start);

        $sales = DB::table('sales')
            ->where('transaction_date', '>=', $from)
            ->selectRaw($this->localDate('transaction_date') . ' as d, COUNT(*) c, COALESCE(SUM(grand_total),0) s')
            ->groupBy('d')->get()->keyBy('d');

        $topups = DB::table('token_topups')
            ->where('status', 'paid')->where('paid_at', '>=', $from)
            ->selectRaw($this->localDate('paid_at') . ' as d, COUNT(*) c, COALESCE(SUM(price),0) s')
            ->groupBy('d')->get()->keyBy('d');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->addDays($i)->toDateString();
            $out[] = [
                'date'           => $day,
                'transactions'   => (int) ($sales[$day]->c ?? 0),
                'revenue'        => (float) ($sales[$day]->s ?? 0),
                'topup_count'    => (int) ($topups[$day]->c ?? 0),
                'topup_revenue'  => (float) ($topups[$day]->s ?? 0),
            ];
        }

        return $out;
    }

    /** Tenant/toko/user baru per bulan + total kumulatif, N bulan terakhir. */
    private function monthlyGrowth(int $months): array
    {
        $start = $this->now->startOfMonth()->subMonthsNoOverflow($months - 1);
        $from  = $this->toApp($start);

        $group = function ($query) use ($from) {
            return $query->where('created_at', '>=', $from)
                ->selectRaw($this->localMonth('created_at') . ' as m, COUNT(*) c')
                ->groupBy('m')->pluck('c', 'm');
        };

        $tenantQ = fn () => Tenant::query()->merchants();
        $storeQ  = fn () => Store::query()->whereHas('tenant', fn ($q) => $q->merchants());
        $userQ   = fn () => User::query()->whereIn('role', ['owner', 'cashier'])
            ->whereHas('tenant', fn ($q) => $q->merchants());

        $new = [
            'tenants' => $group($tenantQ()),
            'stores'  => $group($storeQ()),
            'users'   => $group($userQ()),
        ];

        // total sebelum jendela → dasar untuk kumulatif
        $running = [
            'tenants' => $tenantQ()->where('created_at', '<', $from)->count(),
            'stores'  => $storeQ()->where('created_at', '<', $from)->count(),
            'users'   => $userQ()->where('created_at', '<', $from)->count(),
        ];

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->addMonthsNoOverflow($i);
            $key   = $month->format('Y-m');
            $row   = ['month' => $key, 'label' => $month->locale('id')->translatedFormat('M Y')];
            foreach (['tenants', 'stores', 'users'] as $k) {
                $n = (int) ($new[$k][$key] ?? 0);
                $running[$k] += $n;
                $row[$k . '_new']   = $n;
                $row[$k . '_total'] = $running[$k];
            }
            $out[] = $row;
        }

        return $out;
    }

    // ── Helper zona waktu ────────────────────────────────────────────────

    /** Konversi waktu lokal admin → zona waktu penyimpanan DB. */
    private function toApp(CarbonImmutable|Carbon $date): Carbon
    {
        return Carbon::instance($date)->setTimezone($this->appTz);
    }

    private function offset(string $tz): string
    {
        return CarbonImmutable::now($tz)->format('P'); // mis. +07:00
    }

    private function localDate(string $col): string
    {
        return "DATE(CONVERT_TZ({$col}, '{$this->offset($this->appTz)}', '{$this->offset($this->tz)}'))";
    }

    private function localMonth(string $col): string
    {
        return "DATE_FORMAT(CONVERT_TZ({$col}, '{$this->offset($this->appTz)}', '{$this->offset($this->tz)}'), '%Y-%m')";
    }
}
