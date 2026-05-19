# GoKasir – Backend Laravel (SaaS Multi-Tenant)

> Stack: **Laravel 11**, MySQL, Sanctum (auth), spatie/laravel-permission (role), multi-tenant via `tenant_id`

---

## Daftar Isi

1. [Struktur Folder](#1-struktur-folder)
2. [Setup Awal](#2-setup-awal)
3. [Migrations](#3-migrations)
4. [Models](#4-models)
5. [Middleware & Tenant Scope](#5-middleware--tenant-scope)
6. [Controllers & API Routes](#6-controllers--api-routes)
   - 6.1 Auth
   - 6.2 Toko / Store
   - 6.3 User & Role
   - 6.4 Kategori & Satuan
   - 6.5 Produk
   - 6.6 Stok & Mutasi
   - 6.7 Pelanggan
   - 6.8 Transaksi Penjualan
   - 6.9 Piutang / Hutang Pembeli
   - 6.10 Pengeluaran
   - 6.11 Laporan (Report)
7. [API Route File Lengkap](#7-api-route-file-lengkap)
8. [Request Validation](#8-request-validation)
9. [Resources (API Response)](#9-resources-api-response)
10. [Catatan Tambahan](#10-catatan-tambahan)

---

## 1. Struktur Folder

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── StoreController.php
│   │   ├── UserController.php
│   │   ├── CategoryController.php
│   │   ├── UnitController.php
│   │   ├── ProductController.php
│   │   ├── StockController.php
│   │   ├── StockMovementController.php
│   │   ├── CustomerController.php
│   │   ├── SaleController.php
│   │   ├── ReceivableController.php
│   │   ├── ExpenseController.php
│   │   └── ReportController.php
│   ├── Middleware/
│   │   ├── ResolveTenant.php
│   │   └── EnsureActiveTenant.php
│   ├── Requests/
│   │   ├── StoreRequest.php
│   │   ├── ProductRequest.php
│   │   ├── SaleRequest.php
│   │   ├── StockMovementRequest.php
│   │   ├── ExpenseRequest.php
│   │   └── ReceivablePaymentRequest.php
│   └── Resources/
│       ├── ProductResource.php
│       ├── SaleResource.php
│       └── StockResource.php
├── Models/
│   ├── Tenant.php
│   ├── Store.php
│   ├── User.php
│   ├── Category.php
│   ├── Unit.php
│   ├── Product.php
│   ├── Stock.php
│   ├── StockMovement.php
│   ├── Customer.php
│   ├── Sale.php
│   ├── SaleItem.php
│   ├── Receivable.php
│   ├── ReceivablePayment.php
│   ├── ExpenseCategory.php
│   └── Expense.php
├── Traits/
│   └── BelongsToTenant.php
database/migrations/
routes/
└── api.php
```

---

## 2. Setup Awal

```bash
# Auth
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Permission (opsional, bisa pakai ENUM role saja)
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

php artisan migrate
```
---

## 3. Migrations

> File-file ini menggantikan / melanjutkan dari schema SQL yang sudah ada. Urutan sesuai dependensi FK.

### 3.1 Tenants
```php
// database/migrations/2024_01_01_000001_create_tenants_table.php
Schema::create('tenants', function (Blueprint $table) {
    $table->id();
    $table->string('business_name', 150);
    $table->string('business_type', 100)->nullable();
    $table->string('email', 100)->nullable();
    $table->string('phone', 30)->nullable();
    $table->enum('subscription_plan', ['free', 'basic', 'pro'])->default('free');
    $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
    $table->dateTime('expired_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 3.2 Stores
```php
// database/migrations/2024_01_01_000002_create_stores_table.php
Schema::create('stores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->text('logo')->nullable();
    $table->text('address')->nullable();
    $table->string('city', 100)->nullable();
    $table->string('province', 100)->nullable();
    $table->string('postal_code', 10)->nullable();
    $table->string('phone', 30)->nullable();
    $table->string('email', 100)->nullable();
    $table->text('receipt_footer')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 3.3 Users
```php
// database/migrations/2024_01_01_000003_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
    $table->enum('role', ['owner', 'cashier'])->default('cashier');
    $table->string('name', 100);
    $table->string('email', 100)->unique();
    $table->string('phone', 30)->nullable();
    $table->string('password');
    $table->tinyInteger('status')->default(1);
    $table->dateTime('last_login')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 3.4 Categories
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->timestamp('created_at')->useCurrent();
});
```

### 3.5 Units
```php
Schema::create('units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name', 50);
    $table->string('code', 20);
});
```

### 3.6 Products
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
    $table->string('sku', 100)->nullable();
    $table->string('barcode', 100)->nullable();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->text('image')->nullable();
    $table->decimal('purchase_price', 15, 2)->default(0);
    $table->decimal('selling_price', 15, 2)->default(0);
    $table->integer('min_stock')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 3.7 Stocks
```php
Schema::create('stocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('store_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->decimal('qty', 12, 2)->default(0);
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
    $table->unique(['store_id', 'product_id']);
});
```

### 3.8 Stock Movements
```php
Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('store_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->enum('type', ['in', 'out', 'adjustment']);
    $table->decimal('qty', 12, 2);
    $table->decimal('stock_before', 12, 2)->nullable();
    $table->decimal('stock_after', 12, 2)->nullable();
    $table->string('reference_type', 50)->nullable(); // e.g. "sale", "purchase"
    $table->unsignedBigInteger('reference_id')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();
});
```

### 3.9 Customers
```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->string('name', 100);
    $table->string('phone', 30)->nullable();
    $table->text('address')->nullable();
    $table->decimal('credit_limit', 15, 2)->default(0);
    $table->decimal('current_debt', 15, 2)->default(0);
    $table->timestamp('created_at')->useCurrent();
});
```

### 3.10 Sales & Sales Items
```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('store_id')->constrained();
    $table->string('invoice_number', 100)->unique();
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('cashier_id')->constrained('users');
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('grand_total', 15, 2)->default(0);
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->decimal('change_amount', 15, 2)->default(0);
    $table->enum('payment_method', ['cash','qris','transfer','debit','credit','tempo']);
    $table->enum('payment_status', ['paid','partial','unpaid'])->default('paid');
    $table->text('notes')->nullable();
    $table->dateTime('transaction_date')->useCurrent();
    $table->timestamp('created_at')->useCurrent();
});

Schema::create('sales_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained();
    $table->decimal('qty', 12, 2);
    $table->decimal('price', 15, 2);
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2);
});
```

### 3.11 Receivables
```php
Schema::create('receivables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('customer_id')->constrained();
    $table->foreignId('sale_id')->constrained();
    $table->decimal('total_amount', 15, 2);
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->decimal('remaining_amount', 15, 2);
    $table->date('due_date')->nullable();
    $table->enum('status', ['unpaid','partial','paid','overdue'])->default('unpaid');
    $table->timestamp('created_at')->useCurrent();
});

Schema::create('receivable_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
    $table->dateTime('payment_date')->useCurrent();
    $table->decimal('amount', 15, 2);
    $table->string('payment_method', 50)->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
});
```

### 3.12 Expenses
```php
Schema::create('expense_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->string('name', 100);
});

Schema::create('expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('store_id')->constrained();
    $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
    $table->decimal('amount', 15, 2);
    $table->dateTime('expense_date')->useCurrent();
    $table->text('description')->nullable();
    $table->text('receipt_image')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();
});
```

---

## 4. Models

### Trait: BelongsToTenant
```php
// app/Traits/BelongsToTenant.php
namespace App\Traits;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function scopeForTenant($query, $tenantId = null)
    {
        return $query->where('tenant_id', $tenantId ?? auth()->user()->tenant_id);
    }
}
```

### Tenant
```php
// app/Models/Tenant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name','business_type','email','phone',
        'subscription_plan','status','expired_at',
    ];

    public function stores() { return $this->hasMany(Store::class); }
    public function users()  { return $this->hasMany(User::class); }
}
```

### Store
```php
// app/Models/Store.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Store extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id','name','logo','address','city','province',
        'postal_code','phone','email','receipt_footer',
    ];

    public function tenant()  { return $this->belongsTo(Tenant::class); }
    public function users()   { return $this->hasMany(User::class); }
    public function stocks()  { return $this->hasMany(Stock::class); }
    public function sales()   { return $this->hasMany(Sale::class); }
    public function expenses(){ return $this->hasMany(Expense::class); }
}
```

### User
```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'tenant_id','store_id','role','name','email',
        'phone','password','status',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function store()  { return $this->belongsTo(Store::class); }

    public function isOwner(): bool    { return $this->role === 'owner'; }
    public function isCashier(): bool  { return $this->role === 'cashier'; }
}
```

### Product
```php
// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id','category_id','unit_id','sku','barcode','name',
        'description','image','purchase_price','selling_price',
        'min_stock','is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function category()  { return $this->belongsTo(Category::class); }
    public function unit()      { return $this->belongsTo(Unit::class); }
    public function stocks()    { return $this->hasMany(Stock::class); }

    public function stockAtStore($storeId)
    {
        return $this->stocks()->where('store_id', $storeId)->first()?->qty ?? 0;
    }
}
```

### Stock
```php
// app/Models/Stock.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    public $timestamps = false;
    protected $fillable = ['tenant_id','store_id','product_id','qty'];

    const UPDATED_AT = 'updated_at';

    public function product() { return $this->belongsTo(Product::class); }
    public function store()   { return $this->belongsTo(Store::class); }
}
```

### StockMovement
```php
// app/Models/StockMovement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'tenant_id','store_id','product_id','type','qty',
        'stock_before','stock_after','reference_type','reference_id',
        'notes','created_by',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function product()    { return $this->belongsTo(Product::class); }
    public function store()      { return $this->belongsTo(Store::class); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }
}
```

### Sale & SaleItem
```php
// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Sale extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $fillable = [
        'tenant_id','store_id','invoice_number','customer_id','cashier_id',
        'subtotal','discount_amount','tax_amount','grand_total',
        'paid_amount','change_amount','payment_method','payment_status',
        'notes','transaction_date',
    ];

    public function items()      { return $this->hasMany(SaleItem::class, 'sale_id'); }
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function cashier()    { return $this->belongsTo(User::class, 'cashier_id'); }
    public function store()      { return $this->belongsTo(Store::class); }
    public function receivable() { return $this->hasOne(Receivable::class); }
}

// app/Models/SaleItem.php
class SaleItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['sale_id','product_id','qty','price','discount','subtotal'];

    public function product() { return $this->belongsTo(Product::class); }
}
```

### Customer, Receivable, ReceivablePayment
```php
// app/Models/Customer.php
class Customer extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $fillable = [
        'tenant_id','name','phone','address','credit_limit','current_debt',
    ];

    public function receivables() { return $this->hasMany(Receivable::class); }
}

// app/Models/Receivable.php
class Receivable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id','customer_id','sale_id','total_amount',
        'paid_amount','remaining_amount','due_date','status',
    ];

    public function payments()  { return $this->hasMany(ReceivablePayment::class); }
    public function customer()  { return $this->belongsTo(Customer::class); }
    public function sale()      { return $this->belongsTo(Sale::class); }
}

// app/Models/ReceivablePayment.php
class ReceivablePayment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'receivable_id','payment_date','amount','payment_method','notes','created_by',
    ];
}
```

### Expense
```php
// app/Models/Expense.php
class Expense extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $fillable = [
        'tenant_id','store_id','category_id','amount',
        'expense_date','description','receipt_image','created_by',
    ];

    public function category() { return $this->belongsTo(ExpenseCategory::class); }
    public function store()    { return $this->belongsTo(Store::class); }
}
```

---

## 5. Middleware & Tenant Scope

### ResolveTenant Middleware
```php
// app/Http/Middleware/ResolveTenant.php
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
```

Daftarkan di `bootstrap/app.php` (Laravel 11):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'tenant'  => \App\Http\Middleware\ResolveTenant::class,
        'owner'   => \App\Http\Middleware\EnsureOwner::class,
    ]);
})
```

### EnsureOwner Middleware
```php
// app/Http/Middleware/EnsureOwner.php
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
```

---

## 6. Controllers & API Routes

### Helper: BaseApiController
```php
// app/Http/Controllers/Api/BaseApiController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseApiController extends Controller
{
    protected function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    protected function storeId(): ?int
    {
        return auth()->user()->store_id;
    }

    protected function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $code);
    }

    protected function fail(string $message, int $code = 400)
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }
}
```

---

### 6.1 Auth

```php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    // POST /api/auth/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->fail('Email atau password salah.', 401);
        }

        if ($user->status != 1) {
            return $this->fail('Akun tidak aktif.', 403);
        }

        $user->update(['last_login' => now()]);
        $token = $user->createToken('gokasir')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user'  => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role,
                'store_id' => $user->store_id,
            ],
        ], 'Login berhasil.');
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->ok(null, 'Logout berhasil.');
    }

    // GET /api/auth/me
    public function me(Request $request)
    {
        return $this->ok($request->user()->load('store', 'tenant'));
    }

    // PUT /api/auth/profile
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:30',
        ]);

        $request->user()->update($request->only('name', 'phone'));
        return $this->ok($request->user(), 'Profil diperbarui.');
    }

    // PUT /api/auth/change-password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return $this->fail('Password lama tidak cocok.', 422);
        }

        $request->user()->update(['password' => Hash::make($request->new_password)]);
        return $this->ok(null, 'Password berhasil diubah.');
    }
}
```

---

### 6.2 Toko / Store

```php
// app/Http/Controllers/Api/StoreController.php
namespace App\Http\Controllers\Api;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends BaseApiController
{
    // GET /api/stores
    public function index()
    {
        $stores = Store::forTenant()->withCount('users')->get();
        return $this->ok($stores);
    }

    // POST /api/stores  (owner only)
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'city'  => 'nullable|string|max:100',
        ]);

        $store = Store::create(array_merge(
            $request->only('name','logo','address','city','province','postal_code','phone','email','receipt_footer'),
            ['tenant_id' => $this->tenantId()]
        ));

        return $this->ok($store, 'Toko berhasil dibuat.', 201);
    }

    // GET /api/stores/{id}
    public function show(Store $store)
    {
        $this->authorizeStore($store);
        return $this->ok($store->load('users'));
    }

    // PUT /api/stores/{id}
    public function update(Request $request, Store $store)
    {
        $this->authorizeStore($store);
        $store->update($request->only(
            'name','logo','address','city','province','postal_code','phone','email','receipt_footer'
        ));
        return $this->ok($store, 'Toko diperbarui.');
    }

    // DELETE /api/stores/{id}
    public function destroy(Store $store)
    {
        $this->authorizeStore($store);
        $store->delete();
        return $this->ok(null, 'Toko dihapus.');
    }

    private function authorizeStore(Store $store)
    {
        abort_if($store->tenant_id !== $this->tenantId(), 403, 'Akses ditolak.');
    }
}
```

---

### 6.3 User & Role

```php
// app/Http/Controllers/Api/UserController.php
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseApiController
{
    // GET /api/users
    public function index(Request $request)
    {
        $users = User::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->with('store')
            ->paginate(20);

        return $this->ok($users);
    }

    // POST /api/users
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|in:owner,cashier',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $user = User::create([
            'tenant_id' => $this->tenantId(),
            'store_id'  => $request->store_id,
            'role'      => $request->role,
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
        ]);

        return $this->ok($user, 'User berhasil dibuat.', 201);
    }

    // PUT /api/users/{id}
    public function update(Request $request, User $user)
    {
        abort_if($user->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'name'     => 'sometimes|string|max:100',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'role'     => 'sometimes|in:owner,cashier',
            'store_id' => 'nullable|exists:stores,id',
            'status'   => 'sometimes|in:0,1',
        ]);

        $data = $request->only('name','email','phone','role','store_id','status');
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return $this->ok($user, 'User diperbarui.');
    }

    // DELETE /api/users/{id}
    public function destroy(User $user)
    {
        abort_if($user->tenant_id !== $this->tenantId(), 403);
        abort_if($user->id === auth()->id(), 400, 'Tidak bisa hapus akun sendiri.');
        $user->delete();
        return $this->ok(null, 'User dihapus.');
    }
}
```

---

### 6.4 Kategori & Satuan

```php
// app/Http/Controllers/Api/CategoryController.php
class CategoryController extends BaseApiController
{
    public function index()
    {
        return $this->ok(Category::forTenant()->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $cat = Category::create(['tenant_id' => $this->tenantId(), 'name' => $request->name]);
        return $this->ok($cat, 'Kategori ditambahkan.', 201);
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->tenant_id !== $this->tenantId(), 403);
        $request->validate(['name' => 'required|string|max:100']);
        $category->update(['name' => $request->name]);
        return $this->ok($category, 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        abort_if($category->tenant_id !== $this->tenantId(), 403);
        $category->delete();
        return $this->ok(null, 'Kategori dihapus.');
    }
}

// app/Http/Controllers/Api/UnitController.php
class UnitController extends BaseApiController
{
    public function index()
    {
        return $this->ok(Unit::forTenant()->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
        ]);
        $unit = Unit::create(['tenant_id' => $this->tenantId()] + $request->only('name','code'));
        return $this->ok($unit, 'Satuan ditambahkan.', 201);
    }

    public function update(Request $request, Unit $unit)
    {
        abort_if($unit->tenant_id !== $this->tenantId(), 403);
        $unit->update($request->only('name','code'));
        return $this->ok($unit, 'Satuan diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        abort_if($unit->tenant_id !== $this->tenantId(), 403);
        $unit->delete();
        return $this->ok(null, 'Satuan dihapus.');
    }
}
```

---

### 6.5 Produk

```php
// app/Http/Controllers/Api/ProductController.php
namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    // GET /api/products?search=&category_id=&is_active=
    public function index(Request $request)
    {
        $products = Product::forTenant()
            ->with('category', 'unit')
            ->when($request->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('sku', 'like', "%{$request->search}%")
                       ->orWhere('barcode', 'like', "%{$request->search}%")
                )
            )
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->paginate(20);

        return $this->ok($products);
    }

    // POST /api/products
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:150',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price'=> 'nullable|numeric|min:0',
            'category_id'   => 'nullable|exists:categories,id',
            'unit_id'       => 'nullable|exists:units,id',
            'sku'           => 'nullable|string|max:100',
            'barcode'       => 'nullable|string|max:100',
            'min_stock'     => 'nullable|integer|min:0',
        ]);

        $product = Product::create(array_merge(
            $request->only('category_id','unit_id','sku','barcode','name','description',
                           'image','purchase_price','selling_price','min_stock','is_active'),
            ['tenant_id' => $this->tenantId()]
        ));

        return $this->ok($product->load('category','unit'), 'Produk berhasil dibuat.', 201);
    }

    // GET /api/products/{id}
    public function show(Product $product)
    {
        abort_if($product->tenant_id !== $this->tenantId(), 403);
        return $this->ok($product->load('category','unit','stocks.store'));
    }

    // PUT /api/products/{id}
    public function update(Request $request, Product $product)
    {
        abort_if($product->tenant_id !== $this->tenantId(), 403);

        $product->update($request->only(
            'category_id','unit_id','sku','barcode','name','description',
            'image','purchase_price','selling_price','min_stock','is_active'
        ));

        return $this->ok($product->load('category','unit'), 'Produk diperbarui.');
    }

    // DELETE /api/products/{id}
    public function destroy(Product $product)
    {
        abort_if($product->tenant_id !== $this->tenantId(), 403);
        $product->delete();
        return $this->ok(null, 'Produk dihapus.');
    }

    // GET /api/products/low-stock?store_id=
    public function lowStock(Request $request)
    {
        $storeId = $request->store_id ?? $this->storeId();
        $products = Product::forTenant()
            ->join('stocks', 'products.id', '=', 'stocks.product_id')
            ->where('stocks.store_id', $storeId)
            ->whereColumn('stocks.qty', '<=', 'products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->select('products.*', 'stocks.qty as current_stock')
            ->with('category','unit')
            ->get();

        return $this->ok($products);
    }
}
```

---

### 6.6 Stok & Mutasi

```php
// app/Http/Controllers/Api/StockController.php
namespace App\Http\Controllers\Api;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends BaseApiController
{
    // GET /api/stocks?store_id=
    public function index(Request $request)
    {
        $storeId = $request->store_id ?? $this->storeId();

        $stocks = Stock::where('store_id', $storeId)
            ->where('tenant_id', $this->tenantId())
            ->with('product.category', 'product.unit', 'store')
            ->paginate(20);

        return $this->ok($stocks);
    }
}

// app/Http/Controllers/Api/StockMovementController.php
namespace App\Http\Controllers\Api;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends BaseApiController
{
    // GET /api/stock-movements?store_id=&product_id=&type=&from=&to=
    public function index(Request $request)
    {
        $movements = StockMovement::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type,       fn($q) => $q->where('type', $request->type))
            ->when($request->from,       fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,         fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->with('product', 'store', 'createdBy')
            ->latest('created_at')
            ->paginate(30);

        return $this->ok($movements);
    }

    // POST /api/stock-movements  (in / out / adjustment)
    public function store(Request $request)
    {
        $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:in,out,adjustment',
            'qty'        => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $stock = Stock::firstOrCreate(
                ['store_id' => $request->store_id, 'product_id' => $request->product_id],
                ['tenant_id' => $this->tenantId(), 'qty' => 0]
            );

            $stockBefore = $stock->qty;

            if ($request->type === 'in') {
                $stock->increment('qty', $request->qty);
            } elseif ($request->type === 'out') {
                if ($stock->qty < $request->qty) {
                    return $this->fail('Stok tidak mencukupi.', 422);
                }
                $stock->decrement('qty', $request->qty);
            } else {
                // adjustment: set langsung
                $stock->update(['qty' => $request->qty]);
            }

            $stock->refresh();

            StockMovement::create([
                'tenant_id'      => $this->tenantId(),
                'store_id'       => $request->store_id,
                'product_id'     => $request->product_id,
                'type'           => $request->type,
                'qty'            => $request->qty,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stock->qty,
                'reference_type' => $request->reference_type,
                'reference_id'   => $request->reference_id,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
            ]);

            DB::commit();
            return $this->ok($stock, 'Mutasi stok berhasil.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal memperbarui stok: ' . $e->getMessage(), 500);
        }
    }
}
```

---

### 6.7 Pelanggan

```php
// app/Http/Controllers/Api/CustomerController.php
namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    public function index(Request $request)
    {
        $customers = Customer::forTenant()
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
            )
            ->paginate(20);

        return $this->ok($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create(array_merge(
            $request->only('name','phone','address','credit_limit'),
            ['tenant_id' => $this->tenantId()]
        ));

        return $this->ok($customer, 'Pelanggan ditambahkan.', 201);
    }

    public function show(Customer $customer)
    {
        abort_if($customer->tenant_id !== $this->tenantId(), 403);
        return $this->ok($customer->load('receivables.sale'));
    }

    public function update(Request $request, Customer $customer)
    {
        abort_if($customer->tenant_id !== $this->tenantId(), 403);
        $customer->update($request->only('name','phone','address','credit_limit'));
        return $this->ok($customer, 'Pelanggan diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        abort_if($customer->tenant_id !== $this->tenantId(), 403);
        $customer->delete();
        return $this->ok(null, 'Pelanggan dihapus.');
    }
}
```

---

### 6.8 Transaksi Penjualan

```php
// app/Http/Controllers/Api/SaleController.php
namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Receivable;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends BaseApiController
{
    // GET /api/sales
    public function index(Request $request)
    {
        $sales = Sale::forTenant()
            ->when($request->store_id,      fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->payment_status,fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from,          fn($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->to,            fn($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->with('customer', 'cashier', 'store')
            ->latest('transaction_date')
            ->paginate(20);

        return $this->ok($sales);
    }

    // POST /api/sales
    public function store(Request $request)
    {
        $request->validate([
            'store_id'       => 'required|exists:stores,id',
            'customer_id'    => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:cash,qris,transfer,debit,credit,tempo',
            'paid_amount'    => 'required|numeric|min:0',
            'discount_amount'=> 'nullable|numeric|min:0',
            'tax_amount'     => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Hitung total
            $subtotal = 0;
            foreach ($request->items as $item) {
                $disc     = $item['discount'] ?? 0;
                $subtotal += ($item['price'] * $item['qty']) - $disc;
            }
            $discount    = $request->discount_amount ?? 0;
            $tax         = $request->tax_amount ?? 0;
            $grandTotal  = $subtotal - $discount + $tax;
            $paidAmount  = $request->paid_amount;
            $change      = max(0, $paidAmount - $grandTotal);
            $payStatus   = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            if ($request->payment_method === 'tempo') {
                $payStatus = 'unpaid';
            }

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

            $sale = Sale::create([
                'tenant_id'      => $this->tenantId(),
                'store_id'       => $request->store_id,
                'invoice_number' => $invoiceNumber,
                'customer_id'    => $request->customer_id,
                'cashier_id'     => auth()->id(),
                'subtotal'       => $subtotal,
                'discount_amount'=> $discount,
                'tax_amount'     => $tax,
                'grand_total'    => $grandTotal,
                'paid_amount'    => $paidAmount,
                'change_amount'  => $change,
                'payment_method' => $request->payment_method,
                'payment_status' => $payStatus,
                'notes'          => $request->notes,
                'transaction_date'=> now(),
                'created_at'     => now(),
            ]);

            // Simpan items & kurangi stok
            foreach ($request->items as $item) {
                $disc     = $item['discount'] ?? 0;
                $itemSub  = ($item['price'] * $item['qty']) - $disc;

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty'        => $item['qty'],
                    'price'      => $item['price'],
                    'discount'   => $disc,
                    'subtotal'   => $itemSub,
                ]);

                // Kurangi stok
                $stock = Stock::firstOrCreate(
                    ['store_id' => $request->store_id, 'product_id' => $item['product_id']],
                    ['tenant_id' => $this->tenantId(), 'qty' => 0]
                );
                $stockBefore = $stock->qty;
                $stock->decrement('qty', $item['qty']);
                $stock->refresh();

                StockMovement::create([
                    'tenant_id'      => $this->tenantId(),
                    'store_id'       => $request->store_id,
                    'product_id'     => $item['product_id'],
                    'type'           => 'out',
                    'qty'            => $item['qty'],
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stock->qty,
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            // Buat piutang jika tempo/partial/unpaid
            if (in_array($payStatus, ['unpaid', 'partial']) && $request->customer_id) {
                $remaining = $grandTotal - $paidAmount;
                Receivable::create([
                    'tenant_id'       => $this->tenantId(),
                    'customer_id'     => $request->customer_id,
                    'sale_id'         => $sale->id,
                    'total_amount'    => $grandTotal,
                    'paid_amount'     => $paidAmount,
                    'remaining_amount'=> $remaining,
                    'due_date'        => $request->due_date,
                    'status'          => $payStatus,
                    'created_at'      => now(),
                ]);

                // Update current_debt pelanggan
                Customer::where('id', $request->customer_id)
                    ->increment('current_debt', $remaining);
            }

            DB::commit();
            return $this->ok($sale->load('items.product', 'customer', 'cashier'), 'Transaksi berhasil.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Transaksi gagal: ' . $e->getMessage(), 500);
        }
    }

    // GET /api/sales/{id}
    public function show(Sale $sale)
    {
        abort_if($sale->tenant_id !== $this->tenantId(), 403);
        return $this->ok($sale->load('items.product.unit', 'customer', 'cashier', 'store', 'receivable'));
    }
}
```

---

### 6.9 Piutang / Hutang Pembeli

```php
// app/Http/Controllers/Api/ReceivableController.php
namespace App\Http\Controllers\Api;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends BaseApiController
{
    // GET /api/receivables
    public function index(Request $request)
    {
        $receivables = Receivable::where('tenant_id', $this->tenantId())
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status,      fn($q) => $q->where('status', $request->status))
            ->with('customer', 'sale')
            ->latest('created_at')
            ->paginate(20);

        return $this->ok($receivables);
    }

    // GET /api/receivables/{id}
    public function show(Receivable $receivable)
    {
        abort_if($receivable->tenant_id !== $this->tenantId(), 403);
        return $this->ok($receivable->load('payments.createdBy', 'customer', 'sale.items.product'));
    }

    // POST /api/receivables/{id}/pay  - catat pembayaran piutang
    public function pay(Request $request, Receivable $receivable)
    {
        abort_if($receivable->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'notes'          => 'nullable|string',
        ]);

        if ($request->amount > $receivable->remaining_amount) {
            return $this->fail('Jumlah bayar melebihi sisa piutang.', 422);
        }

        DB::beginTransaction();
        try {
            ReceivablePayment::create([
                'receivable_id'  => $receivable->id,
                'payment_date'   => now(),
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
            ]);

            $newPaid      = $receivable->paid_amount + $request->amount;
            $newRemaining = $receivable->total_amount - $newPaid;
            $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

            $receivable->update([
                'paid_amount'      => $newPaid,
                'remaining_amount' => max(0, $newRemaining),
                'status'           => $newStatus,
            ]);

            // Update current_debt pelanggan
            Customer::where('id', $receivable->customer_id)
                ->decrement('current_debt', $request->amount);

            DB::commit();
            return $this->ok($receivable->fresh(), 'Pembayaran piutang dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat pembayaran.', 500);
        }
    }

    // POST /api/receivables/overdue-check  - tandai overdue
    public function markOverdue()
    {
        $updated = Receivable::where('tenant_id', $this->tenantId())
            ->whereIn('status', ['unpaid','partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        return $this->ok(['updated' => $updated], 'Status piutang overdue diperbarui.');
    }
}
```

---

### 6.10 Pengeluaran

```php
// app/Http/Controllers/Api/ExpenseController.php
namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    // GET /api/expense-categories
    public function categories()
    {
        return $this->ok(ExpenseCategory::forTenant()->get());
    }

    // POST /api/expense-categories
    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $cat = ExpenseCategory::create(['tenant_id' => $this->tenantId(), 'name' => $request->name]);
        return $this->ok($cat, 'Kategori pengeluaran ditambahkan.', 201);
    }

    // GET /api/expenses
    public function index(Request $request)
    {
        $expenses = Expense::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->category_id,fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->from,       fn($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,         fn($q) => $q->whereDate('expense_date', '<=', $request->to))
            ->with('category', 'store')
            ->latest('expense_date')
            ->paginate(20);

        return $this->ok($expenses);
    }

    // POST /api/expenses
    public function store(Request $request)
    {
        $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'amount'      => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'description' => 'nullable|string',
            'expense_date'=> 'nullable|date',
        ]);

        $expense = Expense::create(array_merge(
            $request->only('store_id','category_id','amount','expense_date','description','receipt_image'),
            [
                'tenant_id'   => $this->tenantId(),
                'created_by'  => auth()->id(),
                'expense_date'=> $request->expense_date ?? now(),
                'created_at'  => now(),
            ]
        ));

        return $this->ok($expense->load('category'), 'Pengeluaran dicatat.', 201);
    }

    // PUT /api/expenses/{id}
    public function update(Request $request, Expense $expense)
    {
        abort_if($expense->tenant_id !== $this->tenantId(), 403);
        $expense->update($request->only('category_id','amount','expense_date','description','receipt_image'));
        return $this->ok($expense, 'Pengeluaran diperbarui.');
    }

    // DELETE /api/expenses/{id}
    public function destroy(Expense $expense)
    {
        abort_if($expense->tenant_id !== $this->tenantId(), 403);
        $expense->delete();
        return $this->ok(null, 'Pengeluaran dihapus.');
    }
}
```

---

### 6.11 Laporan (Report)

```php
// app/Http/Controllers/Api/ReportController.php
namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Receivable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseApiController
{
    // GET /api/reports/summary?store_id=&from=&to=
    public function summary(Request $request)
    {
        $tenantId = $this->tenantId();
        $storeId  = $request->store_id;
        $from     = $request->from ?? now()->startOfMonth()->toDateString();
        $to       = $request->to   ?? now()->toDateString();

        $salesQ = Sale::where('tenant_id', $tenantId)
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $totalRevenue  = (clone $salesQ)->sum('grand_total');
        $totalPaid     = (clone $salesQ)->sum('paid_amount');
        $totalDiscount = (clone $salesQ)->sum('discount_amount');
        $totalTax      = (clone $salesQ)->sum('tax_amount');
        $totalTrx      = (clone $salesQ)->count();

        $totalExpense = Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->sum('amount');

        $totalReceivable = Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->when($storeId, fn($q) => $q->whereHas('sale', fn($q2) => $q2->where('store_id', $storeId)))
            ->sum('remaining_amount');

        return $this->ok([
            'period'            => ['from' => $from, 'to' => $to],
            'total_transaction' => $totalTrx,
            'total_revenue'     => $totalRevenue,
            'total_paid'        => $totalPaid,
            'total_discount'    => $totalDiscount,
            'total_tax'         => $totalTax,
            'total_expense'     => $totalExpense,
            'net_income'        => $totalRevenue - $totalExpense,
            'outstanding_receivable' => $totalReceivable,
        ]);
    }

    // GET /api/reports/sales-by-day?store_id=&from=&to=
    public function salesByDay(Request $request)
    {
        $data = Sale::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->whereDate('transaction_date', '>=', $request->from ?? now()->startOfMonth())
            ->whereDate('transaction_date', '<=', $request->to   ?? now())
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(grand_total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/top-products?store_id=&from=&to=&limit=10
    public function topProducts(Request $request)
    {
        $limit = $request->limit ?? 10;

        $data = SaleItem::join('sales', 'sales_items.sale_id', '=', 'sales.id')
            ->join('products', 'sales_items.product_id', '=', 'products.id')
            ->where('sales.tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('sales.store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('sales.transaction_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('sales.transaction_date', '<=', $request->to))
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sales_items.qty) as total_qty'),
                DB::raw('SUM(sales_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/sales-by-payment-method?store_id=&from=&to=
    public function salesByPaymentMethod(Request $request)
    {
        $data = Sale::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->select('payment_method', DB::raw('SUM(grand_total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/stock-value?store_id=
    public function stockValue(Request $request)
    {
        $storeId = $request->store_id ?? $this->storeId();

        $data = Stock::where('stocks.store_id', $storeId)
            ->where('stocks.tenant_id', $this->tenantId())
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.purchase_price',
                'products.selling_price',
                'stocks.qty',
                DB::raw('stocks.qty * products.purchase_price as purchase_value'),
                DB::raw('stocks.qty * products.selling_price as selling_value')
            )
            ->get();

        $summary = [
            'total_purchase_value' => $data->sum('purchase_value'),
            'total_selling_value'  => $data->sum('selling_value'),
            'items'                => $data,
        ];

        return $this->ok($summary);
    }

    // GET /api/reports/cashier-performance?store_id=&from=&to=
    public function cashierPerformance(Request $request)
    {
        $data = Sale::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(sales.id) as total_transaction'),
                DB::raw('SUM(sales.grand_total) as total_revenue')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get();

        return $this->ok($data);
    }

    // GET /api/reports/expense-by-category?store_id=&from=&to=
    public function expenseByCategory(Request $request)
    {
        $data = Expense::where('expenses.tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->from, fn($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('expense_date', '<=', $request->to))
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as category',
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return $this->ok($data);
    }
}
```

---

## 7. API Route File Lengkap

```php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    StoreController,
    UserController,
    CategoryController,
    UnitController,
    ProductController,
    StockController,
    StockMovementController,
    CustomerController,
    SaleController,
    ReceivableController,
    ExpenseController,
    ReportController,
};

// ─── PUBLIC ──────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ─── AUTHENTICATED ───────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::put('profile',          [AuthController::class, 'updateProfile']);
        Route::put('change-password',  [AuthController::class, 'changePassword']);
    });

    // Toko (owner only untuk CUD)
    Route::get('stores', [StoreController::class, 'index']);
    Route::get('stores/{store}', [StoreController::class, 'show']);
    Route::middleware('owner')->group(function () {
        Route::post('stores',           [StoreController::class, 'store']);
        Route::put('stores/{store}',    [StoreController::class, 'update']);
        Route::delete('stores/{store}', [StoreController::class, 'destroy']);
    });

    // Users (owner only)
    Route::middleware('owner')->group(function () {
        Route::get('users',           [UserController::class, 'index']);
        Route::post('users',          [UserController::class, 'store']);
        Route::put('users/{user}',    [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });

    // Kategori & Satuan Produk
    Route::apiResource('categories', CategoryController::class)->except(['show']);
    Route::apiResource('units', UnitController::class)->except(['show']);

    // Produk
    Route::get('products/low-stock', [ProductController::class, 'lowStock']);
    Route::apiResource('products', ProductController::class);

    // Stok
    Route::get('stocks', [StockController::class, 'index']);

    // Mutasi Stok
    Route::get('stock-movements',  [StockMovementController::class, 'index']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);

    // Pelanggan
    Route::apiResource('customers', CustomerController::class);

    // Transaksi Penjualan
    Route::get('sales',       [SaleController::class, 'index']);
    Route::post('sales',      [SaleController::class, 'store']);
    Route::get('sales/{sale}',[SaleController::class, 'show']);

    // Piutang
    Route::get('receivables',                   [ReceivableController::class, 'index']);
    Route::get('receivables/{receivable}',      [ReceivableController::class, 'show']);
    Route::post('receivables/{receivable}/pay', [ReceivableController::class, 'pay']);
    Route::post('receivables/overdue-check',    [ReceivableController::class, 'markOverdue']);

    // Pengeluaran
    Route::get('expense-categories',        [ExpenseController::class, 'categories']);
    Route::post('expense-categories',       [ExpenseController::class, 'storeCategory']);
    Route::get('expenses',                  [ExpenseController::class, 'index']);
    Route::post('expenses',                 [ExpenseController::class, 'store']);
    Route::put('expenses/{expense}',        [ExpenseController::class, 'update']);
    Route::delete('expenses/{expense}',     [ExpenseController::class, 'destroy']);

    // Laporan (owner only)
    Route::middleware('owner')->prefix('reports')->group(function () {
        Route::get('summary',              [ReportController::class, 'summary']);
        Route::get('sales-by-day',         [ReportController::class, 'salesByDay']);
        Route::get('top-products',         [ReportController::class, 'topProducts']);
        Route::get('sales-by-payment',     [ReportController::class, 'salesByPaymentMethod']);
        Route::get('stock-value',          [ReportController::class, 'stockValue']);
        Route::get('cashier-performance',  [ReportController::class, 'cashierPerformance']);
        Route::get('expense-by-category',  [ReportController::class, 'expenseByCategory']);
    });

});
```

---

## 8. Request Validation

Validasi kompleks bisa di-extract ke FormRequest:

```php
// app/Http/Requests/SaleRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'store_id'            => 'required|exists:stores,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'payment_method'      => 'required|in:cash,qris,transfer,debit,credit,tempo',
            'paid_amount'         => 'required|numeric|min:0',
            'discount_amount'     => 'nullable|numeric|min:0',
            'tax_amount'          => 'nullable|numeric|min:0',
            'due_date'            => 'nullable|date|after:today',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.price'       => 'required|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'items.required'             => 'Minimal 1 item transaksi.',
            'items.*.product_id.exists'  => 'Produk tidak ditemukan.',
            'payment_method.in'          => 'Metode bayar tidak valid.',
        ];
    }
}
```

---

## 9. Resources (API Response)

```php
// app/Http/Resources/SaleResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'store'          => $this->whenLoaded('store', fn() => [
                'id'   => $this->store->id,
                'name' => $this->store->name,
            ]),
            'customer'       => $this->whenLoaded('customer', fn() => [
                'id'   => $this->customer?->id,
                'name' => $this->customer?->name,
            ]),
            'cashier'        => $this->whenLoaded('cashier', fn() => [
                'id'   => $this->cashier->id,
                'name' => $this->cashier->name,
            ]),
            'subtotal'        => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount'      => $this->tax_amount,
            'grand_total'     => $this->grand_total,
            'paid_amount'     => $this->paid_amount,
            'change_amount'   => $this->change_amount,
            'payment_method'  => $this->payment_method,
            'payment_status'  => $this->payment_status,
            'notes'           => $this->notes,
            'transaction_date'=> $this->transaction_date,
            'items'           => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
```

---

## 10. Catatan Tambahan

### Multi-Tenant Security
- Setiap query **wajib** filter `tenant_id` dari user yang login.
- Gunakan trait `BelongsToTenant` dan scope `forTenant()` agar konsisten.
- Semua route di bawah middleware `auth:sanctum` + `tenant`.
- Owner bisa akses semua store dalam tenant-nya; cashier hanya store yang assigned.

### Invoice Number
Untuk produksi, gunakan format yang lebih robust:
```php
$invoiceNumber = sprintf('INV-%s-%s-%04d',
    date('Ymd'),
    $request->store_id,
    Sale::where('store_id', $request->store_id)->whereDate('created_at', today())->count() + 1
);
```

### Subscription Plan Enforcement
Cek di middleware atau service class:
```php
// Contoh: limit toko berdasarkan plan
$storeLimit = match($tenant->subscription_plan) {
    'free'  => 1,
    'basic' => 3,
    'pro'   => PHP_INT_MAX,
};
if ($tenant->stores()->count() >= $storeLimit) {
    return $this->fail('Batas toko sesuai paket langganan tercapai.', 403);
}
```

### Upload Gambar
Gunakan Laravel Storage + Spatie Media Library atau manual:
```php
if ($request->hasFile('image')) {
    $path = $request->file('image')->store('products', 'public');
    $data['image'] = asset('storage/' . $path);
}
```

### Queue & Job
- Kirim notifikasi stok minimum via queue job.
- Auto-mark overdue piutang via scheduled command:
```php
// app/Console/Commands/MarkOverdueReceivables.php
Receivable::whereIn('status', ['unpaid','partial'])
    ->whereDate('due_date', '<', now())
    ->update(['status' => 'overdue']);
```
Daftarkan di `routes/console.php`:
```php
Schedule::command('receivables:mark-overdue')->dailyAt('00:05');
```

### Daftar Endpoint Ringkas

| Method | Endpoint | Akses | Keterangan |
|--------|----------|-------|------------|
| POST | /api/auth/login | Public | Login |
| GET | /api/auth/me | Auth | Info user |
| GET | /api/stores | Auth | List toko |
| POST | /api/stores | Owner | Buat toko |
| GET | /api/products | Auth | List produk |
| POST | /api/products | Auth | Tambah produk |
| GET | /api/products/low-stock | Auth | Produk stok rendah |
| GET | /api/stocks | Auth | Stok per toko |
| POST | /api/stock-movements | Auth | Mutasi stok |
| GET | /api/stock-movements | Auth | Histori mutasi |
| GET | /api/customers | Auth | List pelanggan |
| POST | /api/sales | Auth | Buat transaksi |
| GET | /api/sales/{id} | Auth | Detail transaksi |
| GET | /api/receivables | Auth | Daftar piutang |
| POST | /api/receivables/{id}/pay | Auth | Bayar piutang |
| GET | /api/expenses | Auth | Daftar pengeluaran |
| POST | /api/expenses | Auth | Catat pengeluaran |
| GET | /api/reports/summary | Owner | Ringkasan laporan |
| GET | /api/reports/top-products | Owner | Produk terlaris |
| GET | /api/reports/stock-value | Owner | Nilai stok |
| GET | /api/reports/cashier-performance | Owner | Performa kasir |