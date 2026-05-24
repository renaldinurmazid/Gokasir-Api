# GoKasir – Schema Supplier

> Fitur: **Data Supplier**, **Multi-Supplier per Produk**, **Hutang ke Supplier (Payable)**, **Retur ke Supplier**, **Histori Transaksi per Supplier**
> Level: **Per Store** (tiap cabang punya supplier sendiri)

---

## 1. Gambaran Relasi

```
stores
  └── suppliers                  (supplier terdaftar per store)
        ├── product_suppliers    (relasi produk ↔ supplier, bisa banyak)
        ├── purchases            (pembelian / penerimaan barang)
        │     ├── purchase_items (detail item pembelian)
        │     └── stock_movements (otomatis: type='in')
        ├── purchase_returns     (retur barang ke supplier)
        │     └── purchase_return_items
        └── payables             (hutang ke supplier)
              └── payable_payments (cicilan/pelunasan hutang)
```

---

## 2. Migrations

### 2.1 suppliers
```php
// database/migrations/2024_01_04_000001_create_suppliers_table.php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();

    $table->string('name', 150);
    $table->string('code', 50)->nullable();           // kode supplier internal
    $table->string('contact_person', 100)->nullable();
    $table->string('phone', 30)->nullable();
    $table->string('email', 100)->nullable();
    $table->text('address')->nullable();
    $table->string('city', 100)->nullable();
    $table->decimal('credit_limit', 15, 2)->default(0);   // limit hutang ke supplier
    $table->decimal('current_debt', 15, 2)->default(0);   // total hutang berjalan
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);

    $table->timestamps();
    $table->softDeletes();

    $table->index(['store_id', 'is_active']);
});
```

### 2.2 product_suppliers
```php
// database/migrations/2024_01_04_000002_create_product_suppliers_table.php
// Relasi many-to-many produk ↔ supplier
Schema::create('product_suppliers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

    $table->decimal('purchase_price', 15, 2)->default(0);  // harga beli dari supplier ini
    $table->string('supplier_sku', 100)->nullable();        // kode produk versi supplier
    $table->integer('min_order_qty')->default(1);           // minimum order
    $table->boolean('is_preferred')->default(false);        // supplier utama untuk produk ini

    $table->timestamps();

    $table->unique(['store_id', 'product_id', 'supplier_id']);
    $table->index(['store_id', 'product_id']);
});
```

### 2.3 purchases
```php
// database/migrations/2024_01_04_000003_create_purchases_table.php
Schema::create('purchases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

    $table->string('purchase_number', 100)->unique();       // nomor nota/PO
    $table->string('supplier_invoice', 100)->nullable();    // nomor faktur dari supplier
    $table->dateTime('purchase_date');

    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('grand_total', 15, 2)->default(0);
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->decimal('remaining_amount', 15, 2)->default(0);

    $table->enum('payment_method', ['cash','transfer','tempo'])->default('cash');
    $table->enum('payment_status', ['paid','partial','unpaid'])->default('paid');

    // Status penerimaan barang
    $table->enum('receive_status', ['pending','partial','received'])->default('received');

    $table->date('due_date')->nullable();                   // jatuh tempo jika tempo
    $table->text('notes')->nullable();

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
    $table->softDeletes();

    $table->index(['store_id', 'supplier_id']);
    $table->index(['store_id', 'payment_status']);
});
```

### 2.4 purchase_items
```php
// database/migrations/2024_01_04_000004_create_purchase_items_table.php
Schema::create('purchase_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();

    $table->decimal('qty', 12, 2);
    $table->decimal('qty_received', 12, 2)->default(0);    // qty yang benar-benar diterima
    $table->decimal('price', 15, 2);                       // harga beli per unit
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2);
});
```

### 2.5 purchase_returns
```php
// database/migrations/2024_01_04_000005_create_purchase_returns_table.php
Schema::create('purchase_returns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
    $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete(); // referensi PO asal

    $table->string('return_number', 100)->unique();
    $table->dateTime('return_date');

    $table->decimal('total_amount', 15, 2)->default(0);

    // Penyelesaian retur: uang kembali atau pengurang hutang
    $table->enum('resolution', ['refund','debt_reduction'])->default('debt_reduction');
    $table->enum('status', ['draft','confirmed','settled'])->default('confirmed');

    $table->text('reason')->nullable();
    $table->text('notes')->nullable();

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

    $table->index(['store_id', 'supplier_id']);
});
```

### 2.6 purchase_return_items
```php
// database/migrations/2024_01_04_000006_create_purchase_return_items_table.php
Schema::create('purchase_return_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();

    $table->decimal('qty', 12, 2);
    $table->decimal('price', 15, 2);                       // harga saat beli
    $table->decimal('subtotal', 15, 2);
    $table->string('reason', 200)->nullable();             // alasan retur per item
});
```

### 2.7 payables (Hutang ke Supplier)
```php
// database/migrations/2024_01_04_000007_create_payables_table.php
Schema::create('payables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
    $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();

    $table->decimal('total_amount', 15, 2);
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->decimal('remaining_amount', 15, 2);

    $table->date('due_date')->nullable();
    $table->enum('status', ['unpaid','partial','paid','overdue'])->default('unpaid');

    $table->text('notes')->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['store_id', 'supplier_id', 'status']);
});
```

### 2.8 payable_payments (Cicilan/Pelunasan Hutang)
```php
// database/migrations/2024_01_04_000008_create_payable_payments_table.php
Schema::create('payable_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payable_id')->constrained()->cascadeOnDelete();

    $table->dateTime('payment_date')->useCurrent();
    $table->decimal('amount', 15, 2);
    $table->enum('payment_method', ['cash','transfer'])->default('cash');
    $table->text('notes')->nullable();

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();
});
```

---

## 3. Models

### Supplier
```php
// app/Models/Supplier.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Supplier extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id','store_id','name','code','contact_person',
        'phone','email','address','city',
        'credit_limit','current_debt','notes','is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function store()    { return $this->belongsTo(Store::class); }
    public function products() { return $this->belongsToMany(Product::class, 'product_suppliers')
                                             ->withPivot('purchase_price','supplier_sku','min_order_qty','is_preferred')
                                             ->withTimestamps(); }
    public function purchases()       { return $this->hasMany(Purchase::class); }
    public function purchaseReturns() { return $this->hasMany(PurchaseReturn::class); }
    public function payables()        { return $this->hasMany(Payable::class); }

    public function hasDebt(): bool { return $this->current_debt > 0; }
}
```

### Product — tambah relasi
```php
// Tambahkan di app/Models/Product.php
public function suppliers() { return $this->belongsToMany(Supplier::class, 'product_suppliers')
                                          ->withPivot('purchase_price','supplier_sku','min_order_qty','is_preferred')
                                          ->withTimestamps(); }

public function preferredSupplier(int $storeId): ?Supplier
{
    return $this->suppliers()
        ->wherePivot('store_id', $storeId)
        ->wherePivot('is_preferred', true)
        ->first();
}
```

### Purchase
```php
// app/Models/Purchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id','store_id','supplier_id','purchase_number',
        'supplier_invoice','purchase_date','subtotal','discount_amount',
        'tax_amount','grand_total','paid_amount','remaining_amount',
        'payment_method','payment_status','receive_status',
        'due_date','notes','created_by',
    ];

    public function supplier()  { return $this->belongsTo(Supplier::class); }
    public function store()     { return $this->belongsTo(Store::class); }
    public function items()     { return $this->hasMany(PurchaseItem::class); }
    public function payable()   { return $this->hasOne(Payable::class); }
    public function returns()   { return $this->hasMany(PurchaseReturn::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
```

### PurchaseReturn
```php
// app/Models/PurchaseReturn.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','store_id','supplier_id','purchase_id',
        'return_number','return_date','total_amount',
        'resolution','status','reason','notes','created_by',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function items()    { return $this->hasMany(PurchaseReturnItem::class); }
}
```

### Payable
```php
// app/Models/Payable.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id','store_id','supplier_id','purchase_id',
        'total_amount','paid_amount','remaining_amount',
        'due_date','status','notes',
    ];

    public function supplier()  { return $this->belongsTo(Supplier::class); }
    public function purchase()  { return $this->belongsTo(Purchase::class); }
    public function payments()  { return $this->hasMany(PayablePayment::class); }
}
```

---

## 4. Controllers

### SupplierController
```php
// app/Http/Controllers/Api/SupplierController.php
namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseApiController
{
    // GET /api/suppliers?store_id=&search=
    public function index(Request $request)
    {
        $suppliers = Supplier::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
            )
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->withCount('purchases')
            ->paginate(20);

        return $this->ok($suppliers);
    }

    // POST /api/suppliers
    public function store(Request $request)
    {
        $request->validate([
            'store_id'       => 'required|exists:stores,id',
            'name'           => 'required|string|max:150',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:100',
            'credit_limit'   => 'nullable|numeric|min:0',
        ]);

        $supplier = Supplier::create(array_merge(
            $request->only('store_id','name','code','contact_person',
                           'phone','email','address','city','credit_limit','notes'),
            ['tenant_id' => $this->tenantId()]
        ));

        return $this->ok($supplier, 'Supplier ditambahkan.', 201);
    }

    // GET /api/suppliers/{id}
    public function show(Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        return $this->ok($supplier->load('products'));
    }

    // PUT /api/suppliers/{id}
    public function update(Request $request, Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        $supplier->update($request->only(
            'name','code','contact_person','phone','email',
            'address','city','credit_limit','notes','is_active'
        ));

        return $this->ok($supplier, 'Supplier diperbarui.');
    }

    // DELETE /api/suppliers/{id}
    public function destroy(Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);
        $supplier->delete();
        return $this->ok(null, 'Supplier dihapus.');
    }

    // GET /api/suppliers/{id}/history — histori transaksi supplier
    public function history(Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        return $this->ok([
            'supplier'        => $supplier,
            'purchases'       => $supplier->purchases()
                                    ->with('items.product')
                                    ->latest('purchase_date')
                                    ->paginate(10),
            'returns'         => $supplier->purchaseReturns()
                                    ->with('items.product')
                                    ->latest('return_date')
                                    ->paginate(10),
            'payables'        => $supplier->payables()
                                    ->whereIn('status', ['unpaid','partial','overdue'])
                                    ->get(),
            'total_purchase'  => $supplier->purchases()->sum('grand_total'),
            'total_return'    => $supplier->purchaseReturns()->sum('total_amount'),
            'current_debt'    => $supplier->current_debt,
        ]);
    }

    // POST /api/suppliers/{id}/products — tambah relasi produk ke supplier
    public function attachProduct(Request $request, Supplier $supplier)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'purchase_price' => 'required|numeric|min:0',
            'supplier_sku'   => 'nullable|string|max:100',
            'min_order_qty'  => 'nullable|integer|min:1',
            'is_preferred'   => 'nullable|boolean',
        ]);

        // Jika is_preferred = true, reset preferred lain untuk produk ini di store ini
        if ($request->is_preferred) {
            \App\Models\ProductSupplier::where('store_id', $supplier->store_id)
                ->where('product_id', $request->product_id)
                ->update(['is_preferred' => false]);
        }

        $supplier->products()->syncWithoutDetaching([
            $request->product_id => [
                'tenant_id'      => $this->tenantId(),
                'store_id'       => $supplier->store_id,
                'purchase_price' => $request->purchase_price,
                'supplier_sku'   => $request->supplier_sku,
                'min_order_qty'  => $request->min_order_qty ?? 1,
                'is_preferred'   => $request->is_preferred ?? false,
            ]
        ]);

        return $this->ok(null, 'Produk dikaitkan ke supplier.');
    }

    // DELETE /api/suppliers/{id}/products/{productId}
    public function detachProduct(Supplier $supplier, int $productId)
    {
        abort_if($supplier->tenant_id !== $this->tenantId(), 403);
        $supplier->products()->detach($productId);
        return $this->ok(null, 'Produk dilepas dari supplier.');
    }
}
```

### PurchaseController
```php
// app/Http/Controllers/Api/PurchaseController.php
namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Payable;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends BaseApiController
{
    // GET /api/purchases?store_id=&supplier_id=&payment_status=&from=&to=
    public function index(Request $request)
    {
        $purchases = Purchase::where('tenant_id', $this->tenantId())
            ->when($request->store_id,      fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->supplier_id,   fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->payment_status,fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from, fn($q) => $q->whereDate('purchase_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('purchase_date', '<=', $request->to))
            ->with('supplier', 'store', 'createdBy')
            ->latest('purchase_date')
            ->paginate(20);

        return $this->ok($purchases);
    }

    // POST /api/purchases
    public function store(Request $request)
    {
        $request->validate([
            'store_id'        => 'required|exists:stores,id',
            'supplier_id'     => 'required|exists:suppliers,id',
            'purchase_date'   => 'required|date',
            'payment_method'  => 'required|in:cash,transfer,tempo',
            'paid_amount'     => 'required|numeric|min:0',
            'due_date'        => 'nullable|date',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Hitung total
            $subtotal = collect($request->items)->sum(
                fn($i) => ($i['price'] * $i['qty']) - ($i['discount'] ?? 0)
            );
            $discount      = $request->discount_amount ?? 0;
            $tax           = $request->tax_amount ?? 0;
            $grandTotal    = $subtotal - $discount + $tax;
            $paidAmount    = $request->paid_amount;
            $remaining     = max(0, $grandTotal - $paidAmount);
            $payStatus     = $remaining <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            if ($request->payment_method === 'tempo') {
                $payStatus = 'unpaid';
                $remaining = $grandTotal;
                $paidAmount = 0;
            }

            $purchaseNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $purchase = Purchase::create([
                'tenant_id'       => $this->tenantId(),
                'store_id'        => $request->store_id,
                'supplier_id'     => $request->supplier_id,
                'purchase_number' => $purchaseNumber,
                'supplier_invoice'=> $request->supplier_invoice,
                'purchase_date'   => $request->purchase_date,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'remaining_amount'=> $remaining,
                'payment_method'  => $request->payment_method,
                'payment_status'  => $payStatus,
                'receive_status'  => 'received',
                'due_date'        => $request->due_date,
                'notes'           => $request->notes,
                'created_by'      => auth()->id(),
                'created_at'      => now(),
            ]);

            // Simpan items + tambah stok
            foreach ($request->items as $item) {
                $disc    = $item['discount'] ?? 0;
                $itemSub = ($item['price'] * $item['qty']) - $disc;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'qty'         => $item['qty'],
                    'qty_received'=> $item['qty'],
                    'price'       => $item['price'],
                    'discount'    => $disc,
                    'subtotal'    => $itemSub,
                ]);

                // Tambah stok
                $stock = Stock::firstOrCreate(
                    ['store_id' => $request->store_id, 'product_id' => $item['product_id']],
                    ['tenant_id' => $this->tenantId(), 'qty' => 0]
                );
                $stockBefore = $stock->qty;
                $stock->increment('qty', $item['qty']);
                $stock->refresh();

                StockMovement::create([
                    'tenant_id'      => $this->tenantId(),
                    'store_id'       => $request->store_id,
                    'product_id'     => $item['product_id'],
                    'type'           => 'in',
                    'qty'            => $item['qty'],
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stock->qty,
                    'reference_type' => 'purchase',
                    'reference_id'   => $purchase->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            // Buat payable jika ada sisa hutang
            if ($remaining > 0) {
                Payable::create([
                    'tenant_id'       => $this->tenantId(),
                    'store_id'        => $request->store_id,
                    'supplier_id'     => $request->supplier_id,
                    'purchase_id'     => $purchase->id,
                    'total_amount'    => $grandTotal,
                    'paid_amount'     => $paidAmount,
                    'remaining_amount'=> $remaining,
                    'due_date'        => $request->due_date,
                    'status'          => $payStatus,
                    'created_at'      => now(),
                ]);

                // Update current_debt supplier
                Supplier::where('id', $request->supplier_id)
                    ->increment('current_debt', $remaining);
            }

            DB::commit();
            return $this->ok($purchase->load('items.product', 'supplier'), 'Pembelian dicatat.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat pembelian: ' . $e->getMessage(), 500);
        }
    }

    // GET /api/purchases/{id}
    public function show(Purchase $purchase)
    {
        abort_if($purchase->tenant_id !== $this->tenantId(), 403);
        return $this->ok($purchase->load('items.product.unit', 'supplier', 'store', 'payable', 'returns'));
    }
}
```

### PurchaseReturnController
```php
// app/Http/Controllers/Api/PurchaseReturnController.php
namespace App\Http\Controllers\Api;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Payable;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends BaseApiController
{
    // GET /api/purchase-returns?store_id=&supplier_id=
    public function index(Request $request)
    {
        $returns = PurchaseReturn::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->supplier_id,fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->with('supplier', 'purchase')
            ->latest('return_date')
            ->paginate(20);

        return $this->ok($returns);
    }

    // POST /api/purchase-returns
    public function store(Request $request)
    {
        $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'return_date' => 'required|date',
            'resolution'  => 'required|in:refund,debt_reduction',
            'reason'      => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = collect($request->items)
                ->sum(fn($i) => $i['price'] * $i['qty']);

            $returnNumber = 'RTN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $return = PurchaseReturn::create([
                'tenant_id'   => $this->tenantId(),
                'store_id'    => $request->store_id,
                'supplier_id' => $request->supplier_id,
                'purchase_id' => $request->purchase_id,
                'return_number'=> $returnNumber,
                'return_date' => $request->return_date,
                'total_amount'=> $totalAmount,
                'resolution'  => $request->resolution,
                'status'      => 'confirmed',
                'reason'      => $request->reason,
                'notes'       => $request->notes,
                'created_by'  => auth()->id(),
                'created_at'  => now(),
            ]);

            foreach ($request->items as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'         => $item['product_id'],
                    'qty'                => $item['qty'],
                    'price'              => $item['price'],
                    'subtotal'           => $item['price'] * $item['qty'],
                    'reason'             => $item['reason'] ?? null,
                ]);

                // Kurangi stok (barang dikembalikan ke supplier)
                $stock = Stock::where('store_id', $request->store_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($stock) {
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
                        'reference_type' => 'purchase_return',
                        'reference_id'   => $return->id,
                        'created_by'     => auth()->id(),
                        'created_at'     => now(),
                    ]);
                }
            }

            // Jika resolusi = pengurang hutang, update payable
            if ($request->resolution === 'debt_reduction' && $request->purchase_id) {
                $payable = Payable::where('purchase_id', $request->purchase_id)->first();

                if ($payable && $payable->remaining_amount > 0) {
                    $reduction    = min($totalAmount, $payable->remaining_amount);
                    $newPaid      = $payable->paid_amount + $reduction;
                    $newRemaining = max(0, $payable->remaining_amount - $reduction);
                    $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

                    $payable->update([
                        'paid_amount'      => $newPaid,
                        'remaining_amount' => $newRemaining,
                        'status'           => $newStatus,
                    ]);

                    Supplier::where('id', $request->supplier_id)
                        ->decrement('current_debt', $reduction);
                }
            }

            DB::commit();
            return $this->ok($return->load('items.product', 'supplier'), 'Retur dicatat.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat retur: ' . $e->getMessage(), 500);
        }
    }
}
```

### PayableController
```php
// app/Http/Controllers/Api/PayableController.php
namespace App\Http\Controllers\Api;

use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends BaseApiController
{
    // GET /api/payables?store_id=&supplier_id=&status=
    public function index(Request $request)
    {
        $payables = Payable::where('tenant_id', $this->tenantId())
            ->when($request->store_id,   fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->supplier_id,fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status,     fn($q) => $q->where('status', $request->status))
            ->with('supplier', 'purchase')
            ->latest('created_at')
            ->paginate(20);

        return $this->ok($payables);
    }

    // GET /api/payables/{id}
    public function show(Payable $payable)
    {
        abort_if($payable->tenant_id !== $this->tenantId(), 403);
        return $this->ok($payable->load('payments.createdBy', 'supplier', 'purchase.items.product'));
    }

    // POST /api/payables/{id}/pay
    public function pay(Request $request, Payable $payable)
    {
        abort_if($payable->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'notes'          => 'nullable|string',
        ]);

        if ($request->amount > $payable->remaining_amount) {
            return $this->fail('Jumlah bayar melebihi sisa hutang.', 422);
        }

        DB::beginTransaction();
        try {
            PayablePayment::create([
                'payable_id'     => $payable->id,
                'payment_date'   => now(),
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
            ]);

            $newPaid      = $payable->paid_amount + $request->amount;
            $newRemaining = max(0, $payable->total_amount - $newPaid);
            $newStatus    = $newRemaining <= 0 ? 'paid' : 'partial';

            $payable->update([
                'paid_amount'      => $newPaid,
                'remaining_amount' => $newRemaining,
                'status'           => $newStatus,
            ]);

            Supplier::where('id', $payable->supplier_id)
                ->decrement('current_debt', $request->amount);

            DB::commit();
            return $this->ok($payable->fresh(), 'Pembayaran hutang dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal mencatat pembayaran.', 500);
        }
    }

    // POST /api/payables/overdue-check
    public function markOverdue()
    {
        $updated = Payable::where('tenant_id', $this->tenantId())
            ->whereIn('status', ['unpaid','partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        return $this->ok(['updated' => $updated], 'Status hutang overdue diperbarui.');
    }
}
```

---

## 5. API Routes

```php
// routes/api.php — tambahkan di dalam middleware auth:sanctum + tenant

// ── Supplier ─────────────────────────────────────────────────────
Route::prefix('suppliers')->group(function () {
    Route::get('/',                              [SupplierController::class, 'index']);
    Route::post('/',                             [SupplierController::class, 'store']);
    Route::get('/{supplier}',                    [SupplierController::class, 'show']);
    Route::put('/{supplier}',                    [SupplierController::class, 'update']);
    Route::delete('/{supplier}',                 [SupplierController::class, 'destroy']);
    Route::get('/{supplier}/history',            [SupplierController::class, 'history']);
    Route::post('/{supplier}/products',          [SupplierController::class, 'attachProduct']);
    Route::delete('/{supplier}/products/{productId}', [SupplierController::class, 'detachProduct']);
});

// ── Pembelian ─────────────────────────────────────────────────────
Route::prefix('purchases')->group(function () {
    Route::get('/',             [PurchaseController::class, 'index']);
    Route::post('/',            [PurchaseController::class, 'store']);
    Route::get('/{purchase}',   [PurchaseController::class, 'show']);
});

// ── Retur Pembelian ───────────────────────────────────────────────
Route::prefix('purchase-returns')->group(function () {
    Route::get('/',  [PurchaseReturnController::class, 'index']);
    Route::post('/', [PurchaseReturnController::class, 'store']);
});

// ── Hutang ke Supplier (Payable) ──────────────────────────────────
Route::prefix('payables')->group(function () {
    Route::get('/',                       [PayableController::class, 'index']);
    Route::get('/{payable}',              [PayableController::class, 'show']);
    Route::post('/{payable}/pay',         [PayableController::class, 'pay']);
    Route::post('/overdue-check',         [PayableController::class, 'markOverdue']);
});
```

---

## 6. Ringkasan Tabel

| Tabel | Keterangan |
|-------|------------|
| `suppliers` | Data supplier per store |
| `product_suppliers` | Relasi many-to-many produk ↔ supplier + harga beli |
| `purchases` | Nota pembelian dari supplier |
| `purchase_items` | Detail item pembelian |
| `purchase_returns` | Retur barang ke supplier |
| `purchase_return_items` | Detail item retur |
| `payables` | Hutang ke supplier (dibuat otomatis saat pembelian tempo) |
| `payable_payments` | Cicilan/pelunasan hutang ke supplier |

## 7. Ringkasan Endpoint

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | /api/suppliers | Daftar supplier per store |
| POST | /api/suppliers | Tambah supplier |
| GET | /api/suppliers/{id}/history | Histori transaksi supplier |
| POST | /api/suppliers/{id}/products | Kaitkan produk ke supplier |
| GET | /api/purchases | Daftar pembelian |
| POST | /api/purchases | Catat pembelian (otomatis tambah stok) |
| POST | /api/purchase-returns | Catat retur (otomatis kurangi stok) |
| GET | /api/payables | Daftar hutang ke supplier |
| POST | /api/payables/{id}/pay | Bayar hutang |
| POST | /api/payables/overdue-check | Tandai hutang overdue |