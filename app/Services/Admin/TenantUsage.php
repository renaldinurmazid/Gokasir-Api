<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Estimasi pemakaian penyimpanan per tenant.
 *
 * Ukuran DB = jumlah baris milik tenant di setiap tabel × rata-rata panjang baris
 * tabel itu (information_schema.AVG_ROW_LENGTH). Ini ESTIMASI: statistik MySQL
 * diperbarui berkala dan cenderung membesar untuk tabel yang masih sangat kecil.
 */
class TenantUsage
{
    /** Tabel anak (tanpa kolom tenant_id) → [kolom FK, tabel induk yang punya tenant_id]. */
    private const CHILD_TABLES = [
        'sales_items'           => ['sale_id', 'sales'],
        'purchase_items'        => ['purchase_id', 'purchases'],
        'purchase_return_items' => ['purchase_return_id', 'purchase_returns'],
        'table_order_items'     => ['table_order_id', 'table_orders'],
        'payable_payments'      => ['payable_id', 'payables'],
        'receivable_payments'   => ['receivable_id', 'receivables'],
    ];

    /**
     * @param  int[]  $tenantIds
     * @return array<int, array{rows:int, db_bytes:int, tables:array<string, array{rows:int, bytes:int}>}>
     */
    public function forTenants(array $tenantIds): array
    {
        $result = [];
        foreach ($tenantIds as $id) {
            $result[$id] = ['rows' => 0, 'db_bytes' => 0, 'tables' => []];
        }
        if (! $tenantIds) {
            return $result;
        }

        $avg = $this->avgRowLengths();

        // Tabel dengan kolom tenant_id langsung.
        foreach ($this->tenantTables() as $table) {
            $counts = DB::table($table)->whereIn('tenant_id', $tenantIds)
                ->selectRaw('tenant_id, COUNT(*) c')->groupBy('tenant_id')->pluck('c', 'tenant_id');
            $this->accumulate($result, $table, $counts, $avg[$table] ?? 0);
        }

        // Tabel anak: hitung lewat join ke induk.
        foreach (self::CHILD_TABLES as $table => [$fk, $parent]) {
            $counts = DB::table($table)
                ->join($parent, "{$parent}.id", '=', "{$table}.{$fk}")
                ->whereIn("{$parent}.tenant_id", $tenantIds)
                ->selectRaw("{$parent}.tenant_id as tenant_id, COUNT(*) c")
                ->groupBy("{$parent}.tenant_id")->pluck('c', 'tenant_id');
            $this->accumulate($result, $table, $counts, $avg[$table] ?? 0);
        }

        foreach ($result as &$r) {
            uasort($r['tables'], fn ($a, $b) => $b['bytes'] <=> $a['bytes']);
        }

        return $result;
    }

    /** Ukuran berkas upload tenant (logo/banner toko, QRIS, foto produk) di disk public. */
    public function fileBytes(Tenant $tenant, int $limit = 5000): array
    {
        $paths = collect([$tenant->qris])
            ->merge($tenant->stores()->get(['logo', 'banner'])->flatMap(fn ($s) => [$s->logo, $s->banner]))
            ->merge($tenant->products()->whereNotNull('image')->limit($limit)->pluck('image'))
            ->filter(fn ($p) => $p && ! str_starts_with($p, 'http'))
            ->unique();

        $disk  = Storage::disk('public');
        $bytes = 0;
        $found = 0;
        foreach ($paths as $path) {
            try {
                if ($disk->exists($path)) {
                    $bytes += $disk->size($path);
                    $found++;
                }
            } catch (\Throwable) {
                // berkas tak terbaca → abaikan
            }
        }

        return ['bytes' => $bytes, 'files' => $found];
    }

    private function accumulate(array &$result, string $table, $counts, int $avgLen): void
    {
        foreach ($counts as $tenantId => $rows) {
            $bytes = (int) $rows * $avgLen;
            $result[$tenantId]['rows']     += (int) $rows;
            $result[$tenantId]['db_bytes'] += $bytes;
            $result[$tenantId]['tables'][$table] = ['rows' => (int) $rows, 'bytes' => $bytes];
        }
    }

    /** Tabel yang punya kolom tenant_id (otomatis, jadi tabel baru ikut terhitung). */
    private function tenantTables(): array
    {
        return Cache::remember('admin:tenant-tables', 3600, function () {
            $skip = ['tenants', 'admin_audit_logs'];

            return collect(DB::select(
                'SELECT TABLE_NAME t FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?',
                ['tenant_id']
            ))->pluck('t')->reject(fn ($t) => in_array($t, $skip, true))->values()->all();
        });
    }

    private function avgRowLengths(): array
    {
        return Cache::remember('admin:avg-row-length', 3600, function () {
            return collect(DB::select(
                'SELECT TABLE_NAME t, AVG_ROW_LENGTH a FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
            ))->pluck('a', 't')->map(fn ($v) => (int) $v)->all();
        });
    }
}
