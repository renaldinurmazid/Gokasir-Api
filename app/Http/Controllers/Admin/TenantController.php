<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TokenTopup;
use App\Models\User;
use App\Services\Admin\TenantUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TenantController extends Controller
{
    private const SORTS = [
        'business_name' => 'business_name',
        'created_at'    => 'created_at',
        'stores'        => 'stores_count',
        'users'         => 'users_count',
        'products'      => 'products_count',
        'sales'         => 'sales_count',
        'last_sale'     => 'sales_max_transaction_date',
        'token_balance' => 'token_balance',
    ];

    public function index(Request $request, TenantUsage $usage)
    {
        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,active,suspended,expired,deleted'],
            'sort'   => ['nullable', 'in:' . implode(',', array_keys(self::SORTS))],
            'dir'    => ['nullable', 'in:asc,desc'],
        ]);

        $status = $filters['status'] ?? 'all';
        $sort   = $filters['sort'] ?? 'created_at';
        $dir    = $filters['dir'] ?? 'desc';

        $query = Tenant::query()->merchants()
            ->with(['owner:id,tenant_id,name,phone,last_login'])
            ->withCount(['stores', 'users', 'products', 'sales'])
            ->withMax('sales', 'transaction_date');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q = trim($filters['q'] ?? '')) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('business_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('users', fn ($u) => $u->where('name', 'like', $like)->orWhere('phone', 'like', $like));
            });
        }

        $tenants = $query->orderBy(self::SORTS[$sort], $dir)->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $sizes = $usage->forTenants($tenants->getCollection()->pluck('id')->all());

        $tenants->getCollection()->transform(fn (Tenant $t) => [
            ...$this->summary($t),
            'usage' => ['rows' => $sizes[$t->id]['rows'], 'db_bytes' => $sizes[$t->id]['db_bytes']],
        ]);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => ['q' => $filters['q'] ?? '', 'status' => $status, 'sort' => $sort, 'dir' => $dir],
            'counts'  => [
                'all'       => Tenant::merchants()->count(),
                'active'    => Tenant::merchants()->where('status', 'active')->count(),
                'suspended' => Tenant::merchants()->where('status', 'suspended')->count(),
                'expired'   => Tenant::merchants()->where('status', 'expired')->count(),
                'deleted'   => Tenant::merchants()->onlyTrashed()->count(),
            ],
        ]);
    }

    public function show(Tenant $tenant, TenantUsage $usage)
    {
        $tenant->loadCount(['stores', 'users', 'products', 'sales'])
            ->loadMax('sales', 'transaction_date')
            ->load('owner:id,tenant_id,name,phone,email,last_login');

        $stores = $tenant->stores()
            ->withCount(['users', 'sales'])
            ->orderBy('id')->get()
            ->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'city' => $s->city, 'province' => $s->province,
                'phone' => $s->phone, 'users_count' => $s->users_count, 'sales_count' => $s->sales_count,
                'created_at' => $s->created_at?->toIso8601String(),
            ]);

        $users = $tenant->users()->orderByRaw("FIELD(role,'owner','cashier','sales')")->orderBy('id')->get()
            ->map(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'role' => $u->role, 'phone' => $u->phone,
                'email' => $u->email, 'status' => (int) $u->status, 'store_id' => $u->store_id,
                'last_login' => $u->last_login ? \Carbon\Carbon::parse($u->last_login)->toIso8601String() : null,
            ]);

        $salesAgg = Sale::where('tenant_id', $tenant->id)
            ->selectRaw('COUNT(*) c, COALESCE(SUM(grand_total),0) s,
                COALESCE(SUM(transaction_date >= ?),0) c30,
                COALESCE(SUM(CASE WHEN transaction_date >= ? THEN grand_total END),0) s30', [now()->subDays(30), now()->subDays(30)])
            ->first();

        $topupAgg = TokenTopup::where('tenant_id', $tenant->id)->where('status', 'paid')
            ->selectRaw('COUNT(*) c, COALESCE(SUM(price),0) s, COALESCE(SUM(token_amount),0) t')->first();

        $u = $usage->forTenants([$tenant->id])[$tenant->id];
        $files = $usage->fileBytes($tenant);

        $activeProducts = $tenant->products()->where('is_active', true)->count();

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => [
                ...$this->summary($tenant),
                'business_type'       => $tenant->business_type,
                'email'               => $tenant->email,
                'subscription_plan'   => $tenant->subscription_plan,
                'expired_at'          => $tenant->expired_at ? \Carbon\Carbon::parse($tenant->expired_at)->toIso8601String() : null,
                'token_lifetime_used' => (int) $tenant->token_lifetime_used,
                'token_lifetime_topup' => (int) $tenant->token_lifetime_topup,
                'harga_token'         => (float) $tenant->harga_token,
                'owner_email'         => $tenant->owner?->email,
            ],
            'stores' => $stores,
            'users'  => $users,
            'sales'  => [
                'count' => (int) $salesAgg->c, 'revenue' => (float) $salesAgg->s,
                'count_30d' => (int) $salesAgg->c30, 'revenue_30d' => (float) $salesAgg->s30,
            ],
            'topups' => ['count' => (int) $topupAgg->c, 'revenue' => (float) $topupAgg->s, 'tokens' => (int) $topupAgg->t],
            'usage'  => [
                'db_bytes'        => $u['db_bytes'],
                'file_bytes'      => $files['bytes'],
                'file_count'      => $files['files'],
                'rows'            => $u['rows'],
                'tables'          => collect($u['tables'])->map(fn ($t, $name) => ['table' => $name, ...$t])->values(),
                'products'        => $tenant->products_count,
                'active_products' => $activeProducts,
            ],
            'audit' => AdminAuditLog::with('admin:id,name')->where('tenant_id', $tenant->id)
                ->latest('id')->limit(10)->get()
                ->map(fn ($l) => [
                    'id' => $l->id, 'action' => $l->action, 'admin' => $l->admin?->name,
                    'meta' => $l->meta, 'created_at' => $l->created_at?->toIso8601String(),
                ]),
            'impersonate_minutes' => (int) config('admin.impersonate_minutes'),
        ]);
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        DB::transaction(function () use ($tenant, $data) {
            $tenant->update(['status' => 'suspended']);
            $this->revokeTokens($tenant);
            AdminAuditLog::record('tenant.suspend', $tenant, array_filter(['reason' => $data['reason'] ?? null]));
        });

        return back()->with('success', "Tenant \"{$tenant->business_name}\" disuspend. Semua sesi user tenant dicabut.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);
        AdminAuditLog::record('tenant.activate', $tenant);

        return back()->with('success', "Tenant \"{$tenant->business_name}\" diaktifkan kembali.");
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        $request->validate([
            'confirm' => ['required', 'string', fn ($attr, $value, $fail) => $value === $tenant->business_name
                ?: $fail('Nama tenant tidak cocok.')],
        ]);

        DB::transaction(function () use ($tenant) {
            $this->revokeTokens($tenant);
            AdminAuditLog::record('tenant.delete', $tenant);
            $tenant->delete(); // soft delete → bisa dipulihkan
        });

        return redirect()->route('admin.tenants.index')
            ->with('success', "Tenant \"{$tenant->business_name}\" dihapus. Data masih tersimpan dan bisa dipulihkan dari filter \"Terhapus\".");
    }

    public function restore(Tenant $tenant)
    {
        $tenant->restore();
        AdminAuditLog::record('tenant.restore', $tenant);

        return back()->with('success', "Tenant \"{$tenant->business_name}\" dipulihkan.");
    }

    /**
     * Terbitkan token API berumur pendek atas nama owner tenant (Bearer token, sama seperti login aplikasi).
     * Token ditampilkan sekali lewat flash; setiap penerbitan tercatat di audit log.
     */
    public function impersonate(Tenant $tenant)
    {
        abort_if($tenant->trashed(), 404);

        if ($tenant->status !== 'active') {
            return back()->with('error', 'Hanya tenant berstatus aktif yang bisa di-impersonate. Aktifkan dulu tenant ini.');
        }

        $owner = $tenant->users()->where('role', 'owner')->where('status', 1)->orderBy('id')->first();
        if (! $owner) {
            return back()->with('error', 'Tenant ini tidak punya user owner yang aktif.');
        }

        $minutes = (int) config('admin.impersonate_minutes', 60);
        $adminId = auth('admin')->id();
        $token   = $owner->createToken("impersonate:admin-{$adminId}", ['*'], now()->addMinutes($minutes));

        AdminAuditLog::record('tenant.impersonate', $tenant, [
            'as_user_id' => $owner->id, 'as_user' => $owner->name, 'expires_in_minutes' => $minutes,
        ]);

        return back()->with('impersonation', [
            'token'      => $token->plainTextToken,
            'user'       => ['id' => $owner->id, 'name' => $owner->name, 'phone' => $owner->phone],
            'tenant'     => $tenant->business_name,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'api_url'    => url('/api'),
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────────

    private function summary(Tenant $t): array
    {
        $lastSale = $t->sales_max_transaction_date;

        return [
            'id'            => $t->id,
            'business_name' => $t->business_name,
            'phone'         => $t->phone,
            'status'        => $t->status,
            'is_activated'  => (bool) $t->is_activated,
            'deleted'       => $t->trashed(),
            'token_balance' => (int) $t->token_balance,
            'created_at'    => $t->created_at?->toIso8601String(),
            'owner'         => $t->owner ? [
                'name' => $t->owner->name, 'phone' => $t->owner->phone,
                'last_login' => $t->owner->last_login ? \Carbon\Carbon::parse($t->owner->last_login)->toIso8601String() : null,
            ] : null,
            'stores_count'   => $t->stores_count ?? 0,
            'users_count'    => $t->users_count ?? 0,
            'products_count' => $t->products_count ?? 0,
            'sales_count'    => $t->sales_count ?? 0,
            'last_sale_at'   => $lastSale ? \Carbon\Carbon::parse($lastSale, config('app.timezone'))->toIso8601String() : null,
        ];
    }

    /** Cabut semua token Sanctum milik user tenant → app mobile langsung logout (401). */
    private function revokeTokens(Tenant $tenant): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', User::withTrashed()->where('tenant_id', $tenant->id)->select('id'))
            ->delete();
    }
}
