# GoKasir – Update: Harga Token Mitra (Negosiasi)

> Tambahan fitur: **Harga token khusus per tenant/mitra hasil negosiasi**

---

## Latar Belakang

Harga default token saat ini Rp 100/token dirasa terlalu besar untuk segmen UMKM kecil.
Solusinya: admin GoKasir bisa menetapkan **harga token khusus per mitra** hasil negosiasi,
tanpa mengubah harga master yang berlaku untuk umum.

**Aturan sederhana:**
```
jika tenants.harga_token > 0  →  pakai harga mitra (hasil negosiasi)
jika tenants.harga_token = 0  →  pakai harga master dari token_pricing
```

---

## 1. Perubahan Tabel

### Tambah kolom ke tabel `tenants`

```php
// database/migrations/2024_01_03_000001_add_harga_token_to_tenants_table.php
Schema::table('tenants', function (Blueprint $table) {
    $table->decimal('harga_token', 10, 2)->default(0)->after('token_lifetime_topup');
    // 0 = pakai harga master
    // > 0 = harga khusus hasil negosiasi
});
```

**Tidak ada tabel baru.** Hanya 1 kolom tambahan di `tenants`.

---

## 2. Update Model Tenant

```php
// app/Models/Tenant.php

/**
 * Ambil harga token efektif untuk tenant ini.
 * Jika harga_token > 0, pakai harga mitra.
 * Jika tidak, fallback ke harga master dari token_pricing.
 */
public function getEffectiveTokenPrice(\App\Models\TokenPricing $pricing): float
{
    if ($this->harga_token > 0) {
        return (float) $this->harga_token;
    }

    return (float) $pricing->price; // harga master
}

/**
 * Apakah tenant ini punya harga negosiasi?
 */
public function hasMitraPrice(): bool
{
    return $this->harga_token > 0;
}
```

---

## 3. Update TokenTopupController

```php
// app/Http/Controllers/Api/TokenTopupController.php
// Di method store() — bagian kalkulasi harga

$pricing = TokenPricing::active()->findOrFail($request->pricing_id);
$tenant  = auth()->user()->tenant;

// ── Hitung harga efektif ──────────────────────────────────────────
$hargaPerToken = $tenant->getEffectiveTokenPrice($pricing);

if ($pricing->type === 'unit') {
    $qty         = $request->qty ?? 1;
    $tokenAmount = $pricing->token_amount * $qty;
    $totalPrice  = $hargaPerToken * $qty;          // ← pakai harga efektif
} else {
    // Paket: harga paket tidak terpengaruh harga mitra
    // (paket sudah punya harga bundel tersendiri)
    $qty         = 1;
    $tokenAmount = $pricing->total_token;
    $totalPrice  = $pricing->price;               // ← tetap pakai harga paket
}
// ─────────────────────────────────────────────────────────────────

// Sisanya sama seperti sebelumnya...
$orderNumber = 'TKN-' . strtoupper(Str::random(4)) . '-' . time();

$topup = TokenTopup::create([
    // ...
    'price'        => $totalPrice,
    'token_amount' => $tokenAmount,
    // ...
]);
```

> **Catatan:** Harga mitra hanya berlaku untuk pembelian **satuan (unit)**.
> Paket bundel tetap pakai harga yang sudah ditetapkan di `token_pricing`
> karena paket punya struktur harga tersendiri.

---

## 4. Update Response Token Pricing

Saat tenant melihat daftar harga, tampilkan harga yang berlaku untuk mereka:

```php
// app/Http/Controllers/Api/TokenPricingController.php
// Di method index()

public function index()
{
    $tenant  = auth()->user()->tenant;
    $pricing = TokenPricing::active()
        ->orderBy('sort_order')
        ->get()
        ->map(function ($p) use ($tenant) {
            $effectivePrice = $p->type === 'unit'
                ? $tenant->getEffectiveTokenPrice($p)
                : $p->price;  // paket tidak berubah

            return array_merge($p->toArray(), [
                'total_token'       => $p->total_token,
                'effective_price'   => $effectivePrice,          // harga yang berlaku
                'is_mitra_price'    => $tenant->hasMitraPrice() && $p->type === 'unit',
                'price_per_token'   => $p->total_token > 0
                    ? round($effectivePrice / $p->total_token, 2)
                    : 0,
            ]);
        });

    return $this->ok($pricing);
}
```

Contoh response untuk tenant dengan harga mitra Rp 50/token:
```json
[
  {
    "id": 1,
    "type": "unit",
    "name": "Harga Satuan Token",
    "price": 100,
    "effective_price": 50,
    "is_mitra_price": true,
    "total_token": 1,
    "price_per_token": 50
  },
  {
    "id": 2,
    "type": "package",
    "name": "Paket Hemat 500K",
    "price": 500000,
    "effective_price": 500000,
    "is_mitra_price": false,
    "total_token": 5500,
    "price_per_token": 90.91
  }
]
```

---

## 5. API Kelola Harga Mitra (Admin GoKasir)

Endpoint untuk admin/superadmin GoKasir menetapkan harga negosiasi per tenant.

```php
// app/Http/Controllers/Api/Admin/TenantController.php

// PUT /api/admin/tenants/{tenant}/token-price
public function setMitraTokenPrice(Request $request, Tenant $tenant)
{
    $request->validate([
        'harga_token' => 'required|numeric|min:0',
        // 0 = cabut harga mitra, kembali ke harga master
    ]);

    $tenant->update(['harga_token' => $request->harga_token]);

    return $this->ok([
        'tenant_id'    => $tenant->id,
        'business_name'=> $tenant->business_name,
        'harga_token'  => $tenant->harga_token,
        'is_mitra'     => $tenant->hasMitraPrice(),
    ], $request->harga_token > 0
        ? "Harga mitra Rp {$request->harga_token}/token berhasil disimpan."
        : "Harga mitra dicabut. Tenant kembali ke harga master."
    );
}
```

Route:
```php
// routes/api.php — di dalam middleware superadmin/admin
Route::put('admin/tenants/{tenant}/token-price',
    [\App\Http\Controllers\Api\Admin\TenantController::class, 'setMitraTokenPrice']
);
```

---

## 6. Seeder: Contoh Data

```php
// Tenant biasa → pakai harga master Rp 100/token
Tenant::find(1)->update(['harga_token' => 0]);

// Tenant UMKM hasil negosiasi Bu Dian → Rp 50/token
Tenant::find(2)->update(['harga_token' => 50]);

// Mitra strategis → Rp 25/token
Tenant::find(3)->update(['harga_token' => 25]);
```

---

## 7. Ringkasan Perubahan

| File | Perubahan |
|------|-----------|
| `migrations/..._add_harga_token_to_tenants` | Tambah kolom `harga_token` di `tenants` |
| `app/Models/Tenant.php` | Tambah method `getEffectiveTokenPrice()` & `hasMitraPrice()` |
| `app/Http/Controllers/Api/TokenTopupController.php` | Kalkulasi harga pakai `getEffectiveTokenPrice()` |
| `app/Http/Controllers/Api/TokenPricingController.php` | Response tambah field `effective_price` & `is_mitra_price` |
| `app/Http/Controllers/Api/Admin/TenantController.php` | Endpoint baru `setMitraTokenPrice()` |
| `routes/api.php` | Route baru `PUT admin/tenants/{tenant}/token-price` |