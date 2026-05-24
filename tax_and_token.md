# GoKasir – Tax & Token System

> Tambahan fitur: **Pajak default 12%**, **Token per transaksi**, **Topup Token**, **iPaymu Payment Gateway**

---

## Daftar Isi

1. [Konsep Sistem](#1-konsep-sistem)
2. [Perubahan & Tambahan Tabel](#2-perubahan--tambahan-tabel)
3. [Migrations Baru](#3-migrations-baru)
4. [Update Migration Lama](#4-update-migration-lama)
5. [Models](#5-models)
6. [Controllers & API Routes](#6-controllers--api-routes)
   - 6.1 Tax Settings
   - 6.2 Token Pricing & Packages
   - 6.3 Token Topup (iPaymu)
   - 6.4 iPaymu Webhook
   - 6.5 Token Usage Log
7. [iPaymu Integration](#7-ipaymu-integration)
8. [Update SaleController](#8-update-salecontroller)
9. [API Routes Tambahan](#9-api-routes-tambahan)
10. [Catatan & Alur Lengkap](#10-catatan--alur-lengkap)

---

## 1. Konsep Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│  TENANT                                                         │
│  ├── tax_rate         : default 12% (bisa diubah per tenant)    │
│  ├── token_balance    : saldo token saat ini                    │
│  └── token_gift       : 500 token gratis saat pertama daftar   │
│                                                                 │
│  TOKEN PRICING (global, bisa diubah admin kapan saja)           │
│  ├── harga satuan     : Rp 100 / token  (default)               │
│  └── paket            : Rp 500.000 → 5.000 token + bonus 500   │
│                         (contoh paket, bisa ditambah)           │
│                                                                 │
│  SETIAP TRANSAKSI PENJUALAN                                     │
│  └── memotong 1 token dari saldo tenant                         │
│                                                                 │
│  TOPUP ALUR                                                     │
│  ├── Pilih paket / jumlah token                                 │
│  ├── Buat order topup → status: pending                         │
│  ├── Redirect ke iPaymu payment page                            │
│  ├── iPaymu webhook → konfirmasi bayar                          │
│  └── Token otomatis ditambahkan ke saldo tenant                 │
└─────────────────────────────────────────────────────────────────┘
```

### Aturan Token
- Toko baru (tenant baru) otomatis mendapat **500 token gratis**.
- Setiap 1 transaksi penjualan (POST `/api/sales`) memotong **1 token**.
- Jika saldo token = 0, transaksi **ditolak** (kecuali plan tertentu jika ingin dikecualikan).
- Harga token dan paket dikelola di tabel `token_pricing` → bisa diubah kapan saja tanpa deploy ulang.
- History pemakaian & topup tercatat di `token_usage_logs` dan `token_topups`.

---

## 2. Perubahan & Tambahan Tabel

### Tabel yang DIUBAH

| Tabel | Kolom Tambahan |
|-------|----------------|
| `tenants` | `tax_rate DECIMAL(5,2)`, `token_balance INT`, `token_lifetime_used INT` |

### Tabel BARU

| Tabel | Fungsi |
|-------|--------|
| `tax_settings` | Pengaturan pajak per tenant (override global) |
| `token_pricing` | Harga satuan token & daftar paket (dikelola admin) |
| `token_topups` | Order topup token + status pembayaran iPaymu |
| `token_usage_logs` | Log setiap pemakaian & penambahan token |

---

## 3. Migrations Baru

### 3.1 tax_settings
```php
// database/migrations/2024_01_02_000001_create_tax_settings_table.php
Schema::create('tax_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();

    // Pajak utama (PPN)
    $table->decimal('tax_rate', 5, 2)->default(12.00);   // persen, e.g. 12.00
    $table->boolean('tax_enabled')->default(true);        // on/off pajak
    $table->string('tax_name', 50)->default('PPN');       // label di struk
    $table->boolean('tax_inclusive')->default(false);     // harga sudah include pajak?

    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

### 3.2 token_pricing
```php
// database/migrations/2024_01_02_000002_create_token_pricing_table.php
Schema::create('token_pricing', function (Blueprint $table) {
    $table->id();

    // Tipe: 'unit' = harga satuan, 'package' = paket bundel
    $table->enum('type', ['unit', 'package'])->default('unit');

    $table->string('name', 100);                          // "Harga Satuan" / "Paket Hemat 500"
    $table->text('description')->nullable();

    $table->decimal('price', 15, 2)->default(100);        // harga dalam Rupiah
    $table->integer('token_amount')->default(1);          // jumlah token yang didapat
    $table->integer('token_bonus')->default(0);           // bonus token (untuk paket)

    // Total token yang benar-benar diterima = token_amount + token_bonus
    // Contoh paket: price=500000, token_amount=5000, token_bonus=500 → total 5500 token

    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

### 3.3 token_topups
```php
// database/migrations/2024_01_02_000003_create_token_topups_table.php
Schema::create('token_topups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();            // siapa yang order
    $table->foreignId('pricing_id')->nullable()->constrained('token_pricing')->nullOnDelete();

    // Order detail
    $table->string('order_number', 100)->unique();        // internal order ID
    $table->integer('token_amount');                      // token yang akan diterima (amount + bonus)
    $table->decimal('price', 15, 2);                     // total harga yang harus dibayar
    $table->integer('qty')->default(1);                  // untuk pembelian satuan: berapa token

    // iPaymu
    $table->string('ipaymu_trx_id', 100)->nullable()->index();   // ID transaksi dari iPaymu
    $table->string('ipaymu_reference', 100)->nullable();          // reference iPaymu
    $table->string('payment_method', 50)->nullable();             // va, qris, cstore, dll
    $table->string('payment_channel', 50)->nullable();            // bca, bni, indomaret, dll
    $table->text('payment_url')->nullable();                      // URL bayar redirect
    $table->text('ipaymu_raw_response')->nullable();              // raw JSON response iPaymu

    // Status
    $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])
          ->default('pending');
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('expired_at')->nullable();

    // Saldo setelah topup berhasil
    $table->integer('balance_before')->nullable();
    $table->integer('balance_after')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'status']);
});
```

### 3.4 token_usage_logs
```php
// database/migrations/2024_01_02_000004_create_token_usage_logs_table.php
Schema::create('token_usage_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    // Tipe: 'deduct' = pemakaian, 'topup' = isi ulang, 'gift' = bonus/gratis
    $table->enum('type', ['deduct', 'topup', 'gift', 'refund', 'adjustment']);

    $table->integer('amount');                            // jumlah token (+ atau -)
    $table->integer('balance_before');
    $table->integer('balance_after');

    // Referensi ke transaksi terkait
    $table->string('reference_type', 50)->nullable();    // 'sale', 'topup', 'manual'
    $table->unsignedBigInteger('reference_id')->nullable();

    $table->string('description', 200)->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['tenant_id', 'type']);
    $table->index(['tenant_id', 'created_at']);
});
```

---

## 4. Update Migration Lama

### Tambah kolom ke tabel `tenants`
```php
// database/migrations/2024_01_02_000005_add_token_columns_to_tenants_table.php
Schema::table('tenants', function (Blueprint $table) {
    $table->decimal('tax_rate', 5, 2)->default(12.00)->after('expired_at');
    $table->integer('token_balance')->default(500)->after('tax_rate');        // 500 gratis
    $table->integer('token_lifetime_used')->default(0)->after('token_balance');
    $table->integer('token_lifetime_topup')->default(0)->after('token_lifetime_used');
});
```

---

## 5. Models

### TaxSetting
```php
// app/Models/TaxSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'tax_rate', 'tax_enabled',
        'tax_name', 'tax_inclusive', 'updated_by',
    ];

    protected $casts = [
        'tax_enabled'   => 'boolean',
        'tax_inclusive' => 'boolean',
        'tax_rate'      => 'decimal:2',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
}
```

### TokenPricing
```php
// app/Models/TokenPricing.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenPricing extends Model
{
    protected $fillable = [
        'type', 'name', 'description', 'price',
        'token_amount', 'token_bonus', 'is_active',
        'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'price'        => 'decimal:2',
    ];

    // Total token yang diterima pembeli
    public function getTotalTokenAttribute(): int
    {
        return $this->token_amount + $this->token_bonus;
    }

    // Harga per token efektif
    public function getPricePerTokenAttribute(): float
    {
        if ($this->total_token === 0) return 0;
        return round($this->price / $this->total_token, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### TokenTopup
```php
// app/Models/TokenTopup.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenTopup extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'pricing_id',
        'order_number', 'token_amount', 'price', 'qty',
        'ipaymu_trx_id', 'ipaymu_reference',
        'payment_method', 'payment_channel', 'payment_url',
        'ipaymu_raw_response', 'status',
        'paid_at', 'expired_at',
        'balance_before', 'balance_after',
    ];

    protected $casts = [
        'paid_at'    => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function tenant()  { return $this->belongsTo(Tenant::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function pricing() { return $this->belongsTo(TokenPricing::class); }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isPaid(): bool    { return $this->status === 'paid'; }
}
```

### TokenUsageLog
```php
// app/Models/TokenUsageLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'store_id', 'user_id', 'type',
        'amount', 'balance_before', 'balance_after',
        'reference_type', 'reference_id', 'description',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function store()  { return $this->belongsTo(Store::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
```

### Update Model Tenant
```php
// Tambahkan di app/Models/Tenant.php

public function taxSetting()   { return $this->hasOne(TaxSetting::class); }
public function tokenTopups()  { return $this->hasMany(TokenTopup::class); }
public function tokenLogs()    { return $this->hasMany(TokenUsageLog::class); }

// Ambil tax setting atau return default
public function getActiveTaxSetting(): TaxSetting
{
    return $this->taxSetting ?? new TaxSetting([
        'tax_rate'      => $this->tax_rate ?? 12.00,
        'tax_enabled'   => true,
        'tax_name'      => 'PPN',
        'tax_inclusive' => false,
    ]);
}

// Cek apakah masih punya token
public function hasToken(): bool
{
    return $this->token_balance > 0;
}

// Kurangi token (thread-safe dengan DB lock)
public function deductToken(int $amount = 1): bool
{
    if ($this->token_balance < $amount) return false;

    static::where('id', $this->id)
        ->where('token_balance', '>=', $amount)
        ->update([
            'token_balance'       => \DB::raw("token_balance - {$amount}"),
            'token_lifetime_used' => \DB::raw("token_lifetime_used + {$amount}"),
        ]);

    $this->refresh();
    return true;
}

// Tambah token
public function addToken(int $amount): void
{
    static::where('id', $this->id)->update([
        'token_balance'        => \DB::raw("token_balance + {$amount}"),
        'token_lifetime_topup' => \DB::raw("token_lifetime_topup + {$amount}"),
    ]);
    $this->refresh();
}
```

---

## 6. Controllers & API Routes

### TokenService (Business Logic)
```php
// app/Services/TokenService.php
namespace App\Services;

use App\Models\Tenant;
use App\Models\TokenUsageLog;
use Illuminate\Support\Facades\DB;

class TokenService
{
    /**
     * Kurangi token untuk transaksi penjualan.
     * Return false jika token habis.
     */
    public function deductForSale(Tenant $tenant, int $saleId, int $storeId, int $userId): bool
    {
        return DB::transaction(function () use ($tenant, $saleId, $storeId, $userId) {
            $balanceBefore = $tenant->token_balance;

            if (!$tenant->deductToken(1)) {
                return false;
            }

            TokenUsageLog::create([
                'tenant_id'      => $tenant->id,
                'store_id'       => $storeId,
                'user_id'        => $userId,
                'type'           => 'deduct',
                'amount'         => -1,
                'balance_before' => $balanceBefore,
                'balance_after'  => $tenant->token_balance,
                'reference_type' => 'sale',
                'reference_id'   => $saleId,
                'description'    => 'Token digunakan untuk transaksi #' . $saleId,
                'created_at'     => now(),
            ]);

            return true;
        });
    }

    /**
     * Tambah token setelah topup berhasil.
     */
    public function creditFromTopup(Tenant $tenant, int $topupId, int $tokenAmount, int $userId): void
    {
        DB::transaction(function () use ($tenant, $topupId, $tokenAmount, $userId) {
            $balanceBefore = $tenant->token_balance;
            $tenant->addToken($tokenAmount);

            TokenUsageLog::create([
                'tenant_id'      => $tenant->id,
                'user_id'        => $userId,
                'type'           => 'topup',
                'amount'         => $tokenAmount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $tenant->token_balance,
                'reference_type' => 'topup',
                'reference_id'   => $topupId,
                'description'    => "Topup {$tokenAmount} token berhasil.",
                'created_at'     => now(),
            ]);
        });
    }

    /**
     * Token gratis saat tenant baru dibuat.
     */
    public function giftWelcomeToken(Tenant $tenant, int $amount = 500): void
    {
        $balanceBefore = $tenant->token_balance;
        $tenant->addToken($amount);

        TokenUsageLog::create([
            'tenant_id'      => $tenant->id,
            'type'           => 'gift',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $tenant->token_balance,
            'reference_type' => 'tenant',
            'reference_id'   => $tenant->id,
            'description'    => "Bonus {$amount} token untuk toko baru.",
            'created_at'     => now(),
        ]);
    }
}
```

---

### 6.1 Tax Settings Controller

```php
// app/Http/Controllers/Api/TaxSettingController.php
namespace App\Http\Controllers\Api;

use App\Models\TaxSetting;
use Illuminate\Http\Request;

class TaxSettingController extends BaseApiController
{
    // GET /api/tax-settings
    public function show()
    {
        $tenant = auth()->user()->tenant;

        $setting = TaxSetting::firstOrCreate(
            ['tenant_id' => $this->tenantId()],
            [
                'tax_rate'      => $tenant->tax_rate ?? 12.00,
                'tax_enabled'   => true,
                'tax_name'      => 'PPN',
                'tax_inclusive' => false,
            ]
        );

        return $this->ok($setting);
    }

    // PUT /api/tax-settings  (owner only)
    public function update(Request $request)
    {
        $request->validate([
            'tax_rate'      => 'sometimes|numeric|min:0|max:100',
            'tax_enabled'   => 'sometimes|boolean',
            'tax_name'      => 'sometimes|string|max:50',
            'tax_inclusive' => 'sometimes|boolean',
        ]);

        $setting = TaxSetting::updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            array_merge(
                $request->only('tax_rate', 'tax_enabled', 'tax_name', 'tax_inclusive'),
                ['updated_by' => auth()->id()]
            )
        );

        // Sync ke kolom shortcut di tenants
        if ($request->has('tax_rate')) {
            auth()->user()->tenant->update(['tax_rate' => $request->tax_rate]);
        }

        return $this->ok($setting, 'Pengaturan pajak disimpan.');
    }
}
```

---

### 6.2 Token Pricing Controller

```php
// app/Http/Controllers/Api/TokenPricingController.php
namespace App\Http\Controllers\Api;

use App\Models\TokenPricing;
use Illuminate\Http\Request;

class TokenPricingController extends BaseApiController
{
    // GET /api/token-pricing  (publik, semua tenant boleh lihat)
    public function index()
    {
        $pricing = TokenPricing::active()
            ->orderBy('sort_order')
            ->orderBy('type')  // unit dulu, baru package
            ->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'total_token'      => $p->total_token,
                'price_per_token'  => $p->price_per_token,
            ]));

        return $this->ok($pricing);
    }

    // POST /api/admin/token-pricing  (admin/superadmin only)
    public function store(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:unit,package',
            'name'         => 'required|string|max:100',
            'price'        => 'required|numeric|min:1',
            'token_amount' => 'required|integer|min:1',
            'token_bonus'  => 'nullable|integer|min:0',
            'description'  => 'nullable|string',
            'sort_order'   => 'nullable|integer',
        ]);

        $pricing = TokenPricing::create(array_merge(
            $request->only('type','name','description','price','token_amount','token_bonus','sort_order'),
            [
                'token_bonus' => $request->token_bonus ?? 0,
                'is_active'   => true,
                'created_by'  => auth()->id(),
            ]
        ));

        return $this->ok($pricing, 'Harga token ditambahkan.', 201);
    }

    // PUT /api/admin/token-pricing/{id}
    public function update(Request $request, TokenPricing $tokenPricing)
    {
        $request->validate([
            'price'        => 'sometimes|numeric|min:1',
            'token_amount' => 'sometimes|integer|min:1',
            'token_bonus'  => 'sometimes|integer|min:0',
            'is_active'    => 'sometimes|boolean',
        ]);

        $tokenPricing->update($request->only(
            'type','name','description','price',
            'token_amount','token_bonus','is_active','sort_order'
        ));

        return $this->ok($tokenPricing, 'Harga token diperbarui.');
    }

    // DELETE /api/admin/token-pricing/{id}
    public function destroy(TokenPricing $tokenPricing)
    {
        $tokenPricing->update(['is_active' => false]); // soft disable saja
        return $this->ok(null, 'Harga token dinonaktifkan.');
    }
}
```

---

### 6.3 Token Topup Controller (iPaymu)

```php
// app/Http/Controllers/Api/TokenTopupController.php
namespace App\Http\Controllers\Api;

use App\Models\TokenTopup;
use App\Models\TokenPricing;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TokenTopupController extends BaseApiController
{
    public function __construct(protected IPaymuService $ipaymu) {}

    // GET /api/token-topups  - riwayat topup tenant ini
    public function index(Request $request)
    {
        $topups = TokenTopup::where('tenant_id', $this->tenantId())
            ->with('pricing', 'user')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return $this->ok($topups);
    }

    // GET /api/token-balance
    public function balance()
    {
        $tenant = auth()->user()->tenant;
        return $this->ok([
            'token_balance'        => $tenant->token_balance,
            'token_lifetime_used'  => $tenant->token_lifetime_used,
            'token_lifetime_topup' => $tenant->token_lifetime_topup,
        ]);
    }

    /**
     * POST /api/token-topups
     * Body: { pricing_id, qty (opsional untuk tipe 'unit'), payment_method, payment_channel }
     *
     * payment_method: va / qris / cstore
     * payment_channel: bca / bni / bri / mandiri / indomaret / alfamart / qris
     */
    public function store(Request $request)
    {
        $request->validate([
            'pricing_id'     => 'required|exists:token_pricing,id',
            'qty'            => 'nullable|integer|min:1|max:100000',
            'payment_method' => 'required|string|in:va,qris,cstore',
            'payment_channel'=> 'required|string',
        ]);

        $pricing = TokenPricing::active()->findOrFail($request->pricing_id);

        // Hitung token & harga
        if ($pricing->type === 'unit') {
            $qty         = $request->qty ?? 1;
            $tokenAmount = $pricing->token_amount * $qty;  // bonus tidak berlaku di satuan
            $totalPrice  = $pricing->price * $qty;
        } else {
            // Paket: qty selalu 1, bonus berlaku
            $qty         = 1;
            $tokenAmount = $pricing->total_token;
            $totalPrice  = $pricing->price;
        }

        $orderNumber = 'TKN-' . strtoupper(Str::random(4)) . '-' . time();

        // Buat order di DB dulu (status pending)
        $topup = TokenTopup::create([
            'tenant_id'      => $this->tenantId(),
            'user_id'        => auth()->id(),
            'pricing_id'     => $pricing->id,
            'order_number'   => $orderNumber,
            'token_amount'   => $tokenAmount,
            'price'          => $totalPrice,
            'qty'            => $qty,
            'payment_method' => $request->payment_method,
            'payment_channel'=> $request->payment_channel,
            'status'         => 'pending',
            'expired_at'     => now()->addHours(24),
        ]);

        // Kirim ke iPaymu
        try {
            $ipaymuResponse = $this->ipaymu->createPayment([
                'order_number'   => $orderNumber,
                'amount'         => (int) $totalPrice,
                'payment_method' => $request->payment_method,
                'payment_channel'=> $request->payment_channel,
                'buyer_name'     => auth()->user()->name,
                'buyer_email'    => auth()->user()->email,
                'buyer_phone'    => auth()->user()->phone ?? '-',
                'description'    => "Topup {$tokenAmount} Token GoKasir",
                'notify_url'     => config('app.url') . '/api/webhooks/ipaymu',
                'return_url'     => config('app.frontend_url') . '/topup/success',
                'cancel_url'     => config('app.frontend_url') . '/topup/cancel',
            ]);

            $topup->update([
                'ipaymu_trx_id'        => $ipaymuResponse['trx_id'] ?? null,
                'ipaymu_reference'     => $ipaymuResponse['reference_id'] ?? null,
                'payment_url'          => $ipaymuResponse['url'] ?? null,
                'ipaymu_raw_response'  => json_encode($ipaymuResponse),
            ]);

        } catch (\Exception $e) {
            $topup->update(['status' => 'failed']);
            return $this->fail('Gagal membuat pembayaran: ' . $e->getMessage(), 500);
        }

        return $this->ok([
            'order_number'   => $topup->order_number,
            'token_amount'   => $topup->token_amount,
            'price'          => $topup->price,
            'payment_url'    => $topup->payment_url,
            'payment_method' => $topup->payment_method,
            'payment_channel'=> $topup->payment_channel,
            'expired_at'     => $topup->expired_at,
            'status'         => $topup->status,
        ], 'Order topup dibuat. Lanjutkan pembayaran.', 201);
    }

    // GET /api/token-topups/{orderNumber}/check  - cek status manual
    public function checkStatus(string $orderNumber)
    {
        $topup = TokenTopup::where('tenant_id', $this->tenantId())
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return $this->ok([
            'order_number' => $topup->order_number,
            'status'       => $topup->status,
            'token_amount' => $topup->token_amount,
            'paid_at'      => $topup->paid_at,
        ]);
    }
}
```

---

### 6.4 iPaymu Webhook Controller

```php
// app/Http/Controllers/Api/WebhookController.php
namespace App\Http\Controllers\Api;

use App\Models\TokenTopup;
use App\Services\TokenService;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseApiController
{
    public function __construct(
        protected TokenService  $tokenService,
        protected IPaymuService $ipaymu,
    ) {}

    /**
     * POST /api/webhooks/ipaymu
     * iPaymu mengirim notifikasi ke endpoint ini saat transaksi berubah status.
     */
    public function ipaymu(Request $request)
    {
        Log::channel('ipaymu')->info('Webhook received', $request->all());

        // Verifikasi signature iPaymu
        if (!$this->ipaymu->verifySignature($request)) {
            Log::channel('ipaymu')->warning('Invalid signature', $request->all());
            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $trxId  = $request->input('trx_id');
        $status = $request->input('status');  // 1 = berhasil, di iPaymu
        $sid    = $request->input('sid');     // order_number kita

        $topup = TokenTopup::where('order_number', $sid)
            ->orWhere('ipaymu_trx_id', $trxId)
            ->first();

        if (!$topup) {
            Log::channel('ipaymu')->error('Topup not found', ['trx_id' => $trxId, 'sid' => $sid]);
            return response()->json(['status' => 'not_found'], 404);
        }

        // Idempotent: jika sudah paid, abaikan
        if ($topup->isPaid()) {
            return response()->json(['status' => 'already_processed']);
        }

        if ($status == 1 || strtolower($status) === 'berhasil') {
            // Bayar sukses → kredit token
            $tenant = $topup->tenant;
            $balanceBefore = $tenant->token_balance;

            $topup->update([
                'status'         => 'paid',
                'paid_at'        => now(),
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $topup->token_amount,
            ]);

            $this->tokenService->creditFromTopup(
                $tenant,
                $topup->id,
                $topup->token_amount,
                $topup->user_id
            );

            Log::channel('ipaymu')->info('Topup SUCCESS', [
                'order'         => $topup->order_number,
                'token_amount'  => $topup->token_amount,
                'tenant_id'     => $tenant->id,
            ]);

        } elseif (in_array($status, [2, 3, '2', '3'])) {
            // 2 = pending, 3 = kadaluarsa/gagal
            $newStatus = $status == 3 ? 'expired' : 'failed';
            $topup->update(['status' => $newStatus]);
        }

        return response()->json(['status' => 'ok']);
    }
}
```

---

### 6.5 Token Usage Log Controller

```php
// app/Http/Controllers/Api/TokenLogController.php
namespace App\Http\Controllers\Api;

use App\Models\TokenUsageLog;
use Illuminate\Http\Request;

class TokenLogController extends BaseApiController
{
    // GET /api/token-logs?type=&from=&to=
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
```

---

## 7. iPaymu Integration

```php
// app/Services/IPaymuService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IPaymuService
{
    private string $apiKey;
    private string $va;           // Virtual Account iPaymu merchant
    private string $baseUrl;
    private bool   $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('ipaymu.sandbox', true);
        $this->apiKey    = config('ipaymu.api_key');
        $this->va        = config('ipaymu.va');
        $this->baseUrl   = $this->isSandbox
            ? 'https://sandbox.ipaymu.com/api/v2'
            : 'https://my.ipaymu.com/api/v2';
    }

    /**
     * Buat transaksi pembayaran baru di iPaymu.
     */
    public function createPayment(array $params): array
    {
        $body = [
            'product'       => [$params['description']],
            'qty'           => [1],
            'price'         => [$params['amount']],
            'amount'        => $params['amount'],
            'returnUrl'     => $params['return_url'],
            'notifyUrl'     => $params['notify_url'],
            'cancelUrl'     => $params['cancel_url'],
            'referenceId'   => $params['order_number'],
            'buyerName'     => $params['buyer_name'],
            'buyerEmail'    => $params['buyer_email'],
            'buyerPhone'    => $params['buyer_phone'],
            'paymentMethod' => $params['payment_method'],
            'paymentChannel'=> $params['payment_channel'],
        ];

        $response = Http::withHeaders($this->buildHeaders($body))
            ->post($this->baseUrl . '/payment', $body);

        $data = $response->json();

        Log::channel('ipaymu')->info('Create payment response', $data);

        if (!$response->successful() || ($data['Status'] ?? null) != 200) {
            throw new \RuntimeException(
                'iPaymu error: ' . ($data['Message'] ?? 'Unknown error')
            );
        }

        return [
            'trx_id'       => $data['Data']['TransactionId'] ?? null,
            'reference_id' => $params['order_number'],
            'url'          => $data['Data']['Url'] ?? null,
        ];
    }

    /**
     * Verifikasi signature webhook dari iPaymu.
     * iPaymu mengirim header 'signature' = SHA256(va:apikey:body_json)
     */
    public function verifySignature(\Illuminate\Http\Request $request): bool
    {
        $receivedSig  = $request->header('signature') ?? $request->input('signature');
        if (!$receivedSig) return false;

        $bodyString   = $request->getContent();
        $expectedSig  = hash('sha256', strtolower($this->va) . ':' . strtolower($this->apiKey) . ':' . strtolower($bodyString));

        return hash_equals($expectedSig, strtolower($receivedSig));
    }

    /**
     * Build Authorization header iPaymu.
     * Format: SHA256(va:apikey:strtolower(json_body))
     */
    private function buildHeaders(array $body): array
    {
        $bodyString = strtolower(json_encode($body));
        $signature  = hash('sha256', strtolower($this->va) . ':' . strtolower($this->apiKey) . ':' . $bodyString);

        return [
            'Content-Type' => 'application/json',
            'va'           => $this->va,
            'signature'    => $signature,
            'timestamp'    => now()->format('YmdHis'),
        ];
    }
}
```

### Config iPaymu
```php
// config/ipaymu.php
return [
    'sandbox'  => env('IPAYMU_SANDBOX', true),
    'api_key'  => env('IPAYMU_API_KEY', ''),
    'va'       => env('IPAYMU_VA', ''),
];
```

### .env tambahan
```env
IPAYMU_SANDBOX=true
IPAYMU_API_KEY=your_api_key_here
IPAYMU_VA=0000000000000000

# Frontend URL untuk redirect setelah bayar
APP_FRONTEND_URL=https://app.gokasir.id
```

### config/app.php – tambahkan
```php
'frontend_url' => env('APP_FRONTEND_URL', 'http://localhost:3000'),
```

### Logging channel iPaymu
```php
// config/logging.php – tambahkan di 'channels'
'ipaymu' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/ipaymu.log'),
    'level'  => 'debug',
    'days'   => 30,
],
```

---

## 8. Update SaleController

Tambahkan pengecekan & pemotongan token di `SaleController::store()`:

```php
// app/Http/Controllers/Api/SaleController.php
// Tambahkan di constructor:
public function __construct(protected \App\Services\TokenService $tokenService) {}

// Di awal method store(), SEBELUM DB::beginTransaction():
public function store(Request $request)
{
    // ── PENGECEKAN TOKEN ──────────────────────────────────────────
    $tenant = auth()->user()->tenant;

    if (!$tenant->hasToken()) {
        return $this->fail(
            'Saldo token habis. Silakan topup token untuk melanjutkan transaksi.',
            402  // Payment Required
        );
    }
    // ─────────────────────────────────────────────────────────────

    // ... validasi request seperti sebelumnya ...

    DB::beginTransaction();
    try {
        // ... hitung subtotal, grand total, buat sale, items, stock movement ...

        // ── POTONG TOKEN (setelah sale berhasil dibuat) ───────────
        $tokenDeducted = $this->tokenService->deductForSale(
            $tenant,
            $sale->id,
            $request->store_id,
            auth()->id()
        );

        if (!$tokenDeducted) {
            // Race condition: token sudah habis saat proses transaksi
            DB::rollBack();
            return $this->fail('Saldo token tidak mencukupi.', 402);
        }
        // ─────────────────────────────────────────────────────────

        // ... buat receivable jika tempo ...

        DB::commit();

        return $this->ok(
            array_merge(
                $sale->load('items.product', 'customer', 'cashier')->toArray(),
                ['token_balance_remaining' => $tenant->fresh()->token_balance]
            ),
            'Transaksi berhasil.',
            201
        );
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->fail('Transaksi gagal: ' . $e->getMessage(), 500);
    }
}
```

### Update Kalkulasi Pajak di SaleController

Pajak sekarang diambil dari `tax_settings` tenant, bukan dari request:

```php
// Ganti bagian kalkulasi tax di SaleController::store()

// Ambil setting pajak tenant
$taxSetting = $tenant->getActiveTaxSetting();

// Kalkulasi
$subtotal = collect($request->items)->sum(
    fn($item) => ($item['price'] * $item['qty']) - ($item['discount'] ?? 0)
);
$discount   = $request->discount_amount ?? 0;
$afterDisc  = $subtotal - $discount;

// Hitung pajak dari setting
$taxAmount = 0;
if ($taxSetting->tax_enabled) {
    if ($taxSetting->tax_inclusive) {
        // Pajak sudah termasuk harga
        $taxAmount = $afterDisc - ($afterDisc / (1 + ($taxSetting->tax_rate / 100)));
    } else {
        // Pajak ditambahkan di atas
        $taxAmount = $afterDisc * ($taxSetting->tax_rate / 100);
    }
}

$grandTotal = $afterDisc + $taxAmount;

// ... lanjutkan seperti biasa ...
```

---

## 9. API Routes Tambahan

```php
// routes/api.php – tambahkan di dalam middleware group auth:sanctum + tenant

// ── Tax Settings ─────────────────────────────────────────────────
Route::prefix('tax-settings')->middleware('owner')->group(function () {
    Route::get('/',  [TaxSettingController::class, 'show']);
    Route::put('/',  [TaxSettingController::class, 'update']);
});

// ── Token Pricing (publik baca, admin tulis) ─────────────────────
Route::get('token-pricing', [TokenPricingController::class, 'index']);

// ── Token Balance & Logs ─────────────────────────────────────────
Route::get('token-balance',  [TokenTopupController::class, 'balance']);
Route::get('token-logs',     [TokenLogController::class, 'index']);

// ── Token Topup ──────────────────────────────────────────────────
Route::prefix('token-topups')->group(function () {
    Route::get('/',                       [TokenTopupController::class, 'index']);
    Route::post('/',                      [TokenTopupController::class, 'store']);
    Route::get('{orderNumber}/check',     [TokenTopupController::class, 'checkStatus']);
});

// ── Admin: Token Pricing Management (owner/superadmin) ───────────
Route::middleware('owner')->prefix('admin/token-pricing')->group(function () {
    Route::post('/',          [TokenPricingController::class, 'store']);
    Route::put('/{pricing}',  [TokenPricingController::class, 'update']);
    Route::delete('/{pricing}',[TokenPricingController::class, 'destroy']);
});

// ── Webhook iPaymu (public, no auth) ─────────────────────────────
// Harus di LUAR middleware auth
Route::post('webhooks/ipaymu', [WebhookController::class, 'ipaymu'])
    ->withoutMiddleware(['auth:sanctum', 'tenant']);
```

### Exclude webhook dari CSRF (jika pakai session)
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/webhooks/ipaymu',
    ]);
})
```

---

## 10. Catatan & Alur Lengkap

### Seeder: Data Awal Token Pricing
```php
// database/seeders/TokenPricingSeeder.php
TokenPricing::insert([
    [
        'type'         => 'unit',
        'name'         => 'Harga Satuan Token',
        'description'  => 'Rp 100 per token, minimum 10 token',
        'price'        => 100,
        'token_amount' => 1,
        'token_bonus'  => 0,
        'is_active'    => true,
        'sort_order'   => 1,
        'created_at'   => now(),
        'updated_at'   => now(),
    ],
    [
        'type'         => 'package',
        'name'         => 'Paket Hemat 500K',
        'description'  => 'Beli 5.000 token + bonus 500 token gratis',
        'price'        => 500000,
        'token_amount' => 5000,
        'token_bonus'  => 500,
        'is_active'    => true,
        'sort_order'   => 2,
        'created_at'   => now(),
        'updated_at'   => now(),
    ],
    // Tambahkan paket lain sesuai kebutuhan
]);
```

### Seeder: Welcome Token saat Tenant Baru
```php
// Tambahkan di TenantController::store() atau Observer
// app/Observers/TenantObserver.php
class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        // Buat tax setting default
        TaxSetting::create([
            'tenant_id'  => $tenant->id,
            'tax_rate'   => 12.00,
            'tax_enabled'=> true,
            'tax_name'   => 'PPN',
        ]);

        // Catat log token gift (balance sudah 500 dari default kolom)
        TokenUsageLog::create([
            'tenant_id'      => $tenant->id,
            'type'           => 'gift',
            'amount'         => 500,
            'balance_before' => 0,
            'balance_after'  => 500,
            'reference_type' => 'tenant',
            'reference_id'   => $tenant->id,
            'description'    => 'Bonus 500 token untuk toko baru.',
            'created_at'     => now(),
        ]);
    }
}

// Daftarkan di AppServiceProvider::boot()
Tenant::observe(TenantObserver::class);
```

### Alur Topup Token End-to-End

```
Frontend                    Backend (Laravel)              iPaymu
   │                               │                          │
   │  POST /api/token-topups       │                          │
   │  { pricing_id, method }  ───► │                          │
   │                               │  POST /payment  ───────► │
   │                               │  ◄─── { url, trx_id }   │
   │  ◄── { payment_url, status }  │                          │
   │                               │                          │
   │  redirect ke payment_url ─────┼─────────────────────► [User bayar]
   │                               │                          │
   │                               │  ◄── POST /webhooks/ipaymu
   │                               │       { status:1, trx_id }
   │                               │                          │
   │                               │  verifySignature()       │
   │                               │  creditFromTopup()       │
   │                               │  token_balance += N      │
   │                               │                          │
   │  GET /token-topups/{no}/check │                          │
   │  ◄── { status: "paid" }  ◄─── │                          │
```

### Ringkasan Endpoint Baru

| Method | Endpoint | Akses | Keterangan |
|--------|----------|-------|------------|
| GET | /api/tax-settings | Owner | Lihat setting pajak |
| PUT | /api/tax-settings | Owner | Ubah rate pajak |
| GET | /api/token-pricing | Auth | Daftar harga & paket token |
| POST | /api/admin/token-pricing | Owner | Tambah harga/paket baru |
| PUT | /api/admin/token-pricing/{id} | Owner | Update harga/paket |
| GET | /api/token-balance | Auth | Saldo token tenant |
| GET | /api/token-logs | Auth | Riwayat pemakaian token |
| POST | /api/token-topups | Auth | Buat order topup |
| GET | /api/token-topups | Auth | Riwayat topup |
| GET | /api/token-topups/{no}/check | Auth | Cek status topup |
| POST | /api/webhooks/ipaymu | Public | Notifikasi iPaymu |