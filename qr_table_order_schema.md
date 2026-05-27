# GoKasir – Schema Fitur Pesan dari Meja (QR Order)

> Fitur: **QR Code per Meja → Web Order → Kasir Konfirmasi → Bayar (Cash / iPaymu)**
> Tipe bisnis: **Restoran / Kafe**

---

## 1. Gambaran Alur

```
[Customer]                    [Web Order]              [GoKasir App]        [iPaymu]
    │                              │                         │                  │
    │  Scan QR di meja  ──────────►│                         │                  │
    │                              │ tampil menu store        │                  │
    │  Pilih menu + qty            │                         │                  │
    │  Checkout ─────────────────► │                         │                  │
    │                              │ POST /order/place        │                  │
    │                              │ status: pending ────────►│                  │
    │                              │                         │ notif pesanan     │
    │                              │                         │ masuk (meja A)    │
    │                              │                   [Kasir/Waiter]            │
    │                              │                         │ konfirmasi ✓      │
    │                              │                         │ status: confirmed  │
    │                              │                         │                  │
    │  [Pilih bayar cashless]       │                         │                  │
    │  ◄── payment_url ────────────│◄── buat transaksi ──────│──── POST ────────►│
    │  Bayar di iPaymu ────────────┼─────────────────────────┼──────────────────►│
    │                              │                         │◄── webhook paid   │
    │                              │                         │ status: paid      │
    │                              │                         │ stok terpotong    │
    │                              │                         │                  │
    │  [Pilih bayar cash]           │                         │                  │
    │  Datang ke kasir             │                         │                  │
    │                              │                         │ kasir proses bayar│
    │                              │                         │ status: paid      │
```

---

## 2. Tabel Baru

| Tabel | Fungsi |
|-------|--------|
| `tables` | Data meja per store + QR code |
| `table_sessions` | Sesi aktif customer di meja (dari scan QR) |
| `table_orders` | Pesanan dari meja (status: pending → confirmed → paid/cancelled) |
| `table_order_items` | Detail item pesanan |

> **Tidak membuat tabel `sales` baru.** Saat pesanan `confirmed` + `paid`, data dikonversi menjadi record di tabel `sales` yang sudah ada.

---

## 3. Migrations

### 3.1 tables (Meja)
```php
// database/migrations/2024_01_05_000001_create_tables_table.php
Schema::create('tables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();

    $table->string('name', 50);                          // "Meja A", "Meja 1", "VIP 1"
    $table->string('code', 30)->unique();                // kode unik untuk QR: "TBL-ABC123"
    $table->integer('capacity')->default(4);             // kapasitas kursi
    $table->string('location', 100)->nullable();         // "Lantai 1", "Outdoor", "VIP Room"
    $table->boolean('is_active')->default(true);

    // QR Code disimpan sebagai URL atau base64
    $table->text('qr_url')->nullable();                  // URL ke halaman order customer
    $table->text('qr_image')->nullable();                // base64 / path gambar QR

    $table->timestamps();
    $table->softDeletes();

    $table->index(['store_id', 'is_active']);
});
```

### 3.2 table_sessions (Sesi Meja)
```php
// database/migrations/2024_01_05_000002_create_table_sessions_table.php
// Satu sesi = satu kunjungan customer di meja
// Sesi baru dibuat setiap kali QR di-scan & ada order masuk
Schema::create('table_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->foreignId('table_id')->constrained()->cascadeOnDelete();

    $table->string('session_token', 100)->unique();      // token unik sesi, disimpan di browser customer
    $table->integer('pax')->default(1);                  // jumlah tamu
    $table->string('customer_name', 100)->nullable();    // nama customer (opsional, dari form)
    $table->string('customer_phone', 30)->nullable();

    $table->enum('status', ['active', 'ordered', 'paid', 'closed'])->default('active');

    $table->timestamp('opened_at')->useCurrent();
    $table->timestamp('closed_at')->nullable();

    $table->index(['store_id', 'status']);
    $table->index('session_token');
});
```

### 3.3 table_orders (Pesanan dari Meja)
```php
// database/migrations/2024_01_05_000003_create_table_orders_table.php
Schema::create('table_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->foreignId('table_id')->constrained()->cascadeOnDelete();
    $table->foreignId('session_id')->constrained('table_sessions')->cascadeOnDelete();

    $table->string('order_number', 100)->unique();       // "ORD-20240101-0001"

    // Status alur pesanan
    $table->enum('status', [
        'pending',      // baru masuk dari customer, belum dikonfirmasi
        'confirmed',    // kasir/waiter sudah konfirmasi
        'cancelled',    // dibatalkan
        'paid',         // sudah dibayar (sudah jadi sale)
    ])->default('pending');

    // Pembayaran
    $table->enum('payment_type', ['cash', 'cashless'])->nullable();
    $table->enum('payment_status', ['unpaid', 'pending_payment', 'paid'])->default('unpaid');
    $table->string('payment_method', 50)->nullable();    // qris, va, dll (jika cashless)
    $table->string('payment_channel', 50)->nullable();

    // iPaymu
    $table->string('ipaymu_trx_id', 100)->nullable()->index();
    $table->text('payment_url')->nullable();
    $table->timestamp('payment_expired_at')->nullable();

    // Total
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->decimal('grand_total', 15, 2)->default(0);

    // Relasi ke sales (setelah paid)
    $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();

    $table->text('notes')->nullable();                   // catatan dari customer
    $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('confirmed_at')->nullable();

    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

    $table->index(['store_id', 'status']);
    $table->index(['store_id', 'payment_status']);
});
```

### 3.4 table_order_items (Item Pesanan)
```php
// database/migrations/2024_01_05_000004_create_table_order_items_table.php
Schema::create('table_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('table_order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();

    $table->decimal('qty', 12, 2);
    $table->decimal('price', 15, 2);
    $table->decimal('discount', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2);
    $table->text('notes')->nullable();                   // catatan item: "tidak pedas", "tambah keju"

    // Status item (untuk kitchen display jika dibutuhkan nanti)
    $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
});
```

---

## 4. Models

### Table (Meja)
```php
// app/Models/Table.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Table extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'store_id', 'name', 'code',
        'capacity', 'location', 'is_active', 'qr_url', 'qr_image',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function store()    { return $this->belongsTo(Store::class); }
    public function sessions() { return $this->hasMany(TableSession::class); }
    public function orders()   { return $this->hasMany(TableOrder::class); }

    // Sesi aktif saat ini di meja ini
    public function activeSession(): ?TableSession
    {
        return $this->sessions()
            ->whereIn('status', ['active', 'ordered'])
            ->latest('opened_at')
            ->first();
    }

    // URL halaman order customer (disimpan di qr_url)
    public function getOrderUrl(): string
    {
        return config('app.frontend_url') . '/order/' . $this->code;
    }
}
```

### TableSession
```php
// app/Models/TableSession.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'store_id', 'table_id', 'session_token',
        'pax', 'customer_name', 'customer_phone', 'status',
        'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function table()  { return $this->belongsTo(Table::class); }
    public function orders() { return $this->hasMany(TableOrder::class, 'session_id'); }
}
```

### TableOrder
```php
// app/Models/TableOrder.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'store_id', 'table_id', 'session_id',
        'order_number', 'status',
        'payment_type', 'payment_status', 'payment_method', 'payment_channel',
        'ipaymu_trx_id', 'payment_url', 'payment_expired_at',
        'subtotal', 'tax_amount', 'discount_amount', 'grand_total',
        'sale_id', 'notes', 'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at'       => 'datetime',
        'payment_expired_at' => 'datetime',
    ];

    public function table()       { return $this->belongsTo(Table::class); }
    public function session()     { return $this->belongsTo(TableSession::class, 'session_id'); }
    public function items()       { return $this->hasMany(TableOrderItem::class); }
    public function sale()        { return $this->belongsTo(Sale::class); }
    public function confirmedBy() { return $this->belongsTo(User::class, 'confirmed_by'); }

    public function isPending():   bool { return $this->status === 'pending'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isPaid():      bool { return $this->status === 'paid'; }
}
```

### TableOrderItem
```php
// app/Models/TableOrderItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'table_order_id', 'product_id',
        'qty', 'price', 'discount', 'subtotal', 'notes', 'status',
    ];

    public function product() { return $this->belongsTo(Product::class); }
}
```

---

## 5. Controllers

### 5.1 TableController (Manajemen Meja — Kasir/Owner)

```php
// app/Http/Controllers/Api/TableController.php
namespace App\Http\Controllers\Api;

use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableController extends BaseApiController
{
    // GET /api/tables?store_id=
    public function index(Request $request)
    {
        $tables = Table::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->withCount(['orders as pending_orders_count' => fn($q) =>
                $q->where('status', 'pending')
            ])
            ->with('activeSession')
            ->get();

        return $this->ok($tables);
    }

    // POST /api/tables
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name'     => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:100',
        ]);

        $code  = 'TBL-' . strtoupper(Str::random(8));
        $qrUrl = config('app.frontend_url') . '/order/' . $code;

        $table = Table::create(array_merge(
            $request->only('store_id', 'name', 'capacity', 'location'),
            [
                'tenant_id' => $this->tenantId(),
                'code'      => $code,
                'qr_url'    => $qrUrl,
                'is_active' => true,
            ]
        ));

        return $this->ok($table, 'Meja berhasil ditambahkan.', 201);
    }

    // PUT /api/tables/{id}
    public function update(Request $request, Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);
        $table->update($request->only('name', 'capacity', 'location', 'is_active'));
        return $this->ok($table, 'Meja diperbarui.');
    }

    // DELETE /api/tables/{id}
    public function destroy(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);
        $table->delete();
        return $this->ok(null, 'Meja dihapus.');
    }

    // GET /api/tables/{id}/orders — semua pesanan aktif di meja ini
    public function activeOrders(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);

        $orders = $table->orders()
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('items.product', 'session')
            ->latest()
            ->get();

        return $this->ok([
            'table'          => $table,
            'active_session' => $table->activeSession(),
            'orders'         => $orders,
        ]);
    }

    // POST /api/tables/{id}/regenerate-qr — buat ulang QR (jika meja pindah/QR rusak)
    public function regenerateQr(Table $table)
    {
        abort_if($table->tenant_id !== $this->tenantId(), 403);

        $code  = 'TBL-' . strtoupper(Str::random(8));
        $qrUrl = config('app.frontend_url') . '/order/' . $code;

        $table->update(['code' => $code, 'qr_url' => $qrUrl, 'qr_image' => null]);

        return $this->ok($table, 'QR code berhasil diperbarui.');
    }
}
```

### 5.2 PublicOrderController (Diakses Customer via Web)

```php
// app/Http/Controllers/Api/PublicOrderController.php
// Tidak perlu auth Sanctum — diakses public oleh customer
namespace App\Http\Controllers\Api;

use App\Models\Table;
use App\Models\TableSession;
use App\Models\TableOrder;
use App\Models\TableOrderItem;
use App\Models\Product;
use App\Services\IPaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicOrderController extends BaseApiController
{
    public function __construct(protected IPaymuService $ipaymu) {}

    /**
     * GET /public/menu/{tableCode}
     * Customer scan QR → ambil info meja + daftar menu
     */
    public function menu(string $tableCode)
    {
        $table = Table::where('code', $tableCode)
            ->where('is_active', true)
            ->with('store')
            ->firstOrFail();

        $products = Product::where('tenant_id', $table->tenant_id)
            ->where('is_active', true)
            ->with('category', 'unit')
            ->get()
            ->groupBy('category.name'); // grup per kategori untuk tampilan menu

        return $this->ok([
            'store' => [
                'name'    => $table->store->name,
                'logo'    => $table->store->logo,
                'address' => $table->store->address,
            ],
            'table' => [
                'id'       => $table->id,
                'name'     => $table->name,
                'code'     => $table->code,
                'location' => $table->location,
            ],
            'menu' => $products,
        ]);
    }

    /**
     * POST /public/order/{tableCode}/session
     * Buat sesi baru saat customer mulai order
     * Return: session_token (disimpan di browser/localStorage customer)
     */
    public function startSession(Request $request, string $tableCode)
    {
        $request->validate([
            'pax'            => 'nullable|integer|min:1',
            'customer_name'  => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:30',
        ]);

        $table = Table::where('code', $tableCode)->where('is_active', true)->firstOrFail();

        $session = TableSession::create([
            'tenant_id'      => $table->tenant_id,
            'store_id'       => $table->store_id,
            'table_id'       => $table->id,
            'session_token'  => Str::uuid()->toString(),
            'pax'            => $request->pax ?? 1,
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'status'         => 'active',
            'opened_at'      => now(),
        ]);

        return $this->ok([
            'session_token' => $session->session_token,
            'table_name'    => $table->name,
        ], 'Sesi dimulai.');
    }

    /**
     * POST /public/order/{tableCode}/place
     * Customer submit pesanan
     * Header: X-Session-Token: {session_token}
     */
    public function placeOrder(Request $request, string $tableCode)
    {
        $request->validate([
            'session_token'       => 'required|string',
            'payment_type'        => 'required|in:cash,cashless',
            'payment_method'      => 'required_if:payment_type,cashless|string',
            'payment_channel'     => 'required_if:payment_type,cashless|string',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.qty'         => 'required|numeric|min:0.5',
            'items.*.notes'       => 'nullable|string',
        ]);

        $table = Table::where('code', $tableCode)->where('is_active', true)->firstOrFail();

        $session = TableSession::where('session_token', $request->session_token)
            ->where('table_id', $table->id)
            ->whereIn('status', ['active', 'ordered'])
            ->firstOrFail();

        // Ambil tax setting tenant
        $taxSetting = $table->store->tenant->getActiveTaxSetting();

        // Hitung total
        $subtotal = 0;
        $itemsData = [];

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('tenant_id', $table->tenant_id)
                ->where('is_active', true)
                ->firstOrFail();

            $itemSubtotal = $product->selling_price * $item['qty'];
            $subtotal    += $itemSubtotal;

            $itemsData[] = [
                'product_id' => $product->id,
                'qty'        => $item['qty'],
                'price'      => $product->selling_price,
                'discount'   => 0,
                'subtotal'   => $itemSubtotal,
                'notes'      => $item['notes'] ?? null,
                'status'     => 'pending',
            ];
        }

        $taxAmount  = $taxSetting->tax_enabled
            ? round($subtotal * ($taxSetting->tax_rate / 100), 2)
            : 0;
        $grandTotal = $subtotal + $taxAmount;

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        \DB::beginTransaction();
        try {
            $order = TableOrder::create([
                'tenant_id'      => $table->tenant_id,
                'store_id'       => $table->store_id,
                'table_id'       => $table->id,
                'session_id'     => $session->id,
                'order_number'   => $orderNumber,
                'status'         => 'pending',
                'payment_type'   => $request->payment_type,
                'payment_status' => 'unpaid',
                'payment_method' => $request->payment_method ?? null,
                'payment_channel'=> $request->payment_channel ?? null,
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'discount_amount'=> 0,
                'grand_total'    => $grandTotal,
                'notes'          => $request->notes,
                'created_at'     => now(),
            ]);

            foreach ($itemsData as $item) {
                TableOrderItem::create(array_merge(
                    ['table_order_id' => $order->id],
                    $item
                ));
            }

            // Update status sesi
            $session->update(['status' => 'ordered']);

            // Jika cashless → buat link pembayaran iPaymu
            $paymentUrl = null;
            if ($request->payment_type === 'cashless') {
                $ipaymuResponse = $this->ipaymu->createPayment([
                    'order_number'   => $orderNumber,
                    'amount'         => (int) $grandTotal,
                    'payment_method' => $request->payment_method,
                    'payment_channel'=> $request->payment_channel,
                    'buyer_name'     => $session->customer_name ?? 'Customer',
                    'buyer_email'    => 'order@gokasir.id',
                    'buyer_phone'    => $session->customer_phone ?? '-',
                    'description'    => "Order {$orderNumber} - {$table->name}",
                    'notify_url'     => config('app.url') . '/api/webhooks/ipaymu-order',
                    'return_url'     => config('app.frontend_url') . '/order/' . $tableCode . '/status/' . $orderNumber,
                    'cancel_url'     => config('app.frontend_url') . '/order/' . $tableCode . '/cancel/' . $orderNumber,
                ]);

                $order->update([
                    'ipaymu_trx_id'      => $ipaymuResponse['trx_id'] ?? null,
                    'payment_url'        => $ipaymuResponse['url'] ?? null,
                    'payment_expired_at' => now()->addHours(1),
                    'payment_status'     => 'pending_payment',
                ]);

                $paymentUrl = $ipaymuResponse['url'] ?? null;
            }

            \DB::commit();

            return $this->ok([
                'order_number'   => $order->order_number,
                'table_name'     => $table->name,
                'grand_total'    => $order->grand_total,
                'payment_type'   => $order->payment_type,
                'payment_status' => $order->payment_status,
                'payment_url'    => $paymentUrl,   // null jika cash
                'status'         => $order->status,
                'message'        => $request->payment_type === 'cash'
                    ? 'Pesanan diterima. Silakan bayar di kasir.'
                    : 'Pesanan diterima. Lanjutkan pembayaran.',
            ], 'Pesanan berhasil dikirim.', 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->fail('Gagal mengirim pesanan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /public/order/{tableCode}/status/{orderNumber}
     * Customer cek status pesanan
     */
    public function orderStatus(string $tableCode, string $orderNumber)
    {
        $table = Table::where('code', $tableCode)->firstOrFail();

        $order = TableOrder::where('order_number', $orderNumber)
            ->where('table_id', $table->id)
            ->with('items.product')
            ->firstOrFail();

        return $this->ok([
            'order_number'   => $order->order_number,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'grand_total'    => $order->grand_total,
            'items'          => $order->items->map(fn($i) => [
                'product_name' => $i->product->name,
                'qty'          => $i->qty,
                'price'        => $i->price,
                'subtotal'     => $i->subtotal,
                'notes'        => $i->notes,
            ]),
            'message' => match($order->status) {
                'pending'    => 'Menunggu konfirmasi dari kasir.',
                'confirmed'  => 'Pesanan dikonfirmasi, sedang diproses.',
                'paid'       => 'Pesanan selesai. Terima kasih!',
                'cancelled'  => 'Pesanan dibatalkan.',
                default      => '-',
            },
        ]);
    }
}
```

### 5.3 TableOrderController (Kasir — Kelola Pesanan Masuk)

```php
// app/Http/Controllers/Api/TableOrderController.php
namespace App\Http\Controllers\Api;

use App\Models\TableOrder;
use App\Models\TableSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableOrderController extends BaseApiController
{
    public function __construct(protected TokenService $tokenService) {}

    /**
     * GET /api/table-orders?store_id=&status=&table_id=
     * Kasir lihat semua pesanan masuk
     */
    public function index(Request $request)
    {
        $orders = TableOrder::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->table_id, fn($q) => $q->where('table_id', $request->table_id))
            ->with('table', 'items.product', 'session', 'confirmedBy')
            ->latest()
            ->paginate(30);

        return $this->ok($orders);
    }

    /**
     * GET /api/table-orders/pending
     * Shortcut: semua pesanan pending yang butuh dikonfirmasi kasir
     */
    public function pending(Request $request)
    {
        $orders = TableOrder::where('tenant_id', $this->tenantId())
            ->where('store_id', $request->store_id ?? $this->storeId())
            ->where('status', 'pending')
            ->with('table', 'items.product', 'session')
            ->oldest()
            ->get();

        return $this->ok($orders);
    }

    /**
     * POST /api/table-orders/{id}/confirm
     * Kasir konfirmasi pesanan → status: confirmed
     */
    public function confirm(TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if (!$tableOrder->isPending()) {
            return $this->fail('Pesanan sudah dikonfirmasi atau tidak dalam status pending.', 422);
        }

        $tableOrder->update([
            'status'       => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        // Update status item
        $tableOrder->items()->update(['status' => 'confirmed']);

        return $this->ok($tableOrder->load('items.product', 'table'), 'Pesanan dikonfirmasi.');
    }

    /**
     * POST /api/table-orders/{id}/cancel
     * Kasir batalkan pesanan
     */
    public function cancel(Request $request, TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if ($tableOrder->isPaid()) {
            return $this->fail('Pesanan yang sudah dibayar tidak bisa dibatalkan.', 422);
        }

        $tableOrder->update(['status' => 'cancelled']);
        $tableOrder->items()->update(['status' => 'cancelled']);

        return $this->ok(null, 'Pesanan dibatalkan.');
    }

    /**
     * POST /api/table-orders/{id}/process-payment
     * Kasir proses pembayaran CASH (setelah customer datang ke kasir)
     * → convert TableOrder menjadi Sale
     */
    public function processPayment(Request $request, TableOrder $tableOrder)
    {
        abort_if($tableOrder->tenant_id !== $this->tenantId(), 403);

        if (!$tableOrder->isConfirmed()) {
            return $this->fail('Pesanan harus dikonfirmasi dulu sebelum diproses pembayaran.', 422);
        }

        if ($tableOrder->isPaid()) {
            return $this->fail('Pesanan ini sudah dibayar.', 422);
        }

        $request->validate([
            'paid_amount'    => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,qris,transfer,debit,credit',
        ]);

        // Cek token
        $tenant = auth()->user()->tenant;
        if (!$tenant->hasToken()) {
            return $this->fail('Saldo token habis. Silakan topup token.', 402);
        }

        DB::beginTransaction();
        try {
            $grandTotal  = $tableOrder->grand_total;
            $paidAmount  = $request->paid_amount;
            $change      = max(0, $paidAmount - $grandTotal);
            $invoiceNum  = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            // Buat Sale dari TableOrder
            $sale = Sale::create([
                'tenant_id'       => $this->tenantId(),
                'store_id'        => $tableOrder->store_id,
                'invoice_number'  => $invoiceNum,
                'customer_id'     => null,
                'cashier_id'      => auth()->id(),
                'subtotal'        => $tableOrder->subtotal,
                'discount_amount' => $tableOrder->discount_amount,
                'tax_amount'      => $tableOrder->tax_amount,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'change_amount'   => $change,
                'payment_method'  => $request->payment_method,
                'payment_status'  => 'paid',
                'notes'           => 'Order dari ' . $tableOrder->table->name
                                    . ' | ' . $tableOrder->order_number,
                'transaction_date'=> now(),
                'created_at'      => now(),
            ]);

            // Pindahkan items ke sale_items + kurangi stok
            foreach ($tableOrder->items()->where('status', 'confirmed')->get() as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item->product_id,
                    'qty'        => $item->qty,
                    'price'      => $item->price,
                    'discount'   => $item->discount,
                    'subtotal'   => $item->subtotal,
                ]);

                $stock = Stock::firstOrCreate(
                    ['store_id' => $tableOrder->store_id, 'product_id' => $item->product_id],
                    ['tenant_id' => $this->tenantId(), 'qty' => 0]
                );
                $stockBefore = $stock->qty;
                $stock->decrement('qty', $item->qty);
                $stock->refresh();

                StockMovement::create([
                    'tenant_id'      => $this->tenantId(),
                    'store_id'       => $tableOrder->store_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'out',
                    'qty'            => $item->qty,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stock->qty,
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'created_by'     => auth()->id(),
                    'created_at'     => now(),
                ]);
            }

            // Update TableOrder → paid
            $tableOrder->update([
                'status'         => 'paid',
                'payment_status' => 'paid',
                'sale_id'        => $sale->id,
            ]);

            // Tutup sesi meja
            $tableOrder->session->update([
                'status'    => 'paid',
                'closed_at' => now(),
            ]);

            // Potong token
            $this->tokenService->deductForSale(
                $tenant,
                $sale->id,
                $tableOrder->store_id,
                auth()->id()
            );

            DB::commit();
            return $this->ok([
                'sale'         => $sale->load('items.product'),
                'change'       => $change,
                'table_name'   => $tableOrder->table->name,
                'order_number' => $tableOrder->order_number,
            ], 'Pembayaran berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal memproses pembayaran: ' . $e->getMessage(), 500);
        }
    }
}
```

### 5.4 Webhook iPaymu untuk Order Meja

```php
// Tambahkan method di WebhookController.php yang sudah ada

/**
 * POST /api/webhooks/ipaymu-order
 * Notifikasi pembayaran cashless dari customer via web order
 */
public function ipaymuOrder(Request $request)
{
    \Log::channel('ipaymu')->info('Order webhook received', $request->all());

    if (!$this->ipaymu->verifySignature($request)) {
        return response()->json(['status' => 'invalid_signature'], 403);
    }

    $trxId  = $request->input('trx_id');
    $status = $request->input('status');
    $sid    = $request->input('sid');  // order_number

    $order = TableOrder::where('order_number', $sid)
        ->orWhere('ipaymu_trx_id', $trxId)
        ->first();

    if (!$order || $order->isPaid()) {
        return response()->json(['status' => 'skipped']);
    }

    if ($status == 1 || strtolower($status) === 'berhasil') {
        $tenant = $order->store->tenant;

        if (!$tenant->hasToken()) {
            \Log::channel('ipaymu')->warning('Token habis saat webhook order', ['order' => $sid]);
            return response()->json(['status' => 'token_empty'], 200);
        }

        DB::beginTransaction();
        try {
            $invoiceNum = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $sale = Sale::create([
                'tenant_id'       => $order->tenant_id,
                'store_id'        => $order->store_id,
                'invoice_number'  => $invoiceNum,
                'cashier_id'      => 1, // sistem / bot — sesuaikan dengan user sistem
                'subtotal'        => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount'      => $order->tax_amount,
                'grand_total'     => $order->grand_total,
                'paid_amount'     => $order->grand_total,
                'change_amount'   => 0,
                'payment_method'  => $order->payment_method ?? 'qris',
                'payment_status'  => 'paid',
                'notes'           => 'Cashless order dari ' . $order->table->name
                                    . ' | ' . $order->order_number,
                'transaction_date'=> now(),
                'created_at'      => now(),
            ]);

            foreach ($order->items()->where('status', 'confirmed')->get() as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item->product_id,
                    'qty'        => $item->qty,
                    'price'      => $item->price,
                    'discount'   => $item->discount,
                    'subtotal'   => $item->subtotal,
                ]);

                $stock = Stock::firstOrCreate(
                    ['store_id' => $order->store_id, 'product_id' => $item->product_id],
                    ['tenant_id' => $order->tenant_id, 'qty' => 0]
                );
                $stockBefore = $stock->qty;
                $stock->decrement('qty', $item->qty);
                $stock->refresh();

                StockMovement::create([
                    'tenant_id'      => $order->tenant_id,
                    'store_id'       => $order->store_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'out',
                    'qty'            => $item->qty,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stock->qty,
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'created_at'     => now(),
                ]);
            }

            $order->update([
                'status'         => 'paid',
                'payment_status' => 'paid',
                'sale_id'        => $sale->id,
            ]);

            $order->session->update(['status' => 'paid', 'closed_at' => now()]);

            $this->tokenService->deductForSale(
                $tenant, $sale->id, $order->store_id, $order->session->id
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::channel('ipaymu')->error('Gagal proses order webhook', ['error' => $e->getMessage()]);
        }
    }

    return response()->json(['status' => 'ok']);
}
```

---

## 6. API Routes

```php
// routes/api.php

// ── PUBLIC (customer, tanpa auth) ────────────────────────────────
Route::prefix('public')->group(function () {
    Route::get('menu/{tableCode}',                   [PublicOrderController::class, 'menu']);
    Route::post('order/{tableCode}/session',          [PublicOrderController::class, 'startSession']);
    Route::post('order/{tableCode}/place',            [PublicOrderController::class, 'placeOrder']);
    Route::get('order/{tableCode}/status/{orderNumber}', [PublicOrderController::class, 'orderStatus']);
});

// ── Webhook cashless order (tanpa auth) ───────────────────────────
Route::post('webhooks/ipaymu-order', [WebhookController::class, 'ipaymuOrder']);

// ── AUTHENTICATED (kasir/owner) ───────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Manajemen meja
    Route::prefix('tables')->group(function () {
        Route::get('/',                        [TableController::class, 'index']);
        Route::post('/',                       [TableController::class, 'store']);
        Route::put('/{table}',                 [TableController::class, 'update']);
        Route::delete('/{table}',              [TableController::class, 'destroy']);
        Route::get('/{table}/orders',          [TableController::class, 'activeOrders']);
        Route::post('/{table}/regenerate-qr',  [TableController::class, 'regenerateQr']);
    });

    // Kelola pesanan masuk
    Route::prefix('table-orders')->group(function () {
        Route::get('/',                              [TableOrderController::class, 'index']);
        Route::get('/pending',                       [TableOrderController::class, 'pending']);
        Route::post('/{tableOrder}/confirm',         [TableOrderController::class, 'confirm']);
        Route::post('/{tableOrder}/cancel',          [TableOrderController::class, 'cancel']);
        Route::post('/{tableOrder}/process-payment', [TableOrderController::class, 'processPayment']);
    });

});
```

---

## 7. Ringkasan Tabel & Endpoint

### Tabel Baru

| Tabel | Keterangan |
|-------|------------|
| `tables` | Data meja per store, menyimpan kode & URL QR |
| `table_sessions` | Sesi kunjungan customer per meja |
| `table_orders` | Pesanan dari customer via web, status: pending → confirmed → paid |
| `table_order_items` | Detail item pesanan + catatan per item |

### Endpoint Ringkas

| Method | Endpoint | Siapa | Keterangan |
|--------|----------|-------|------------|
| GET | /api/public/menu/{code} | Customer | Lihat menu + info meja |
| POST | /api/public/order/{code}/session | Customer | Mulai sesi order |
| POST | /api/public/order/{code}/place | Customer | Submit pesanan |
| GET | /api/public/order/{code}/status/{no} | Customer | Cek status pesanan |
| GET | /api/table-orders/pending | Kasir | Pesanan masuk belum dikonfirmasi |
| POST | /api/table-orders/{id}/confirm | Kasir | Konfirmasi pesanan |
| POST | /api/table-orders/{id}/cancel | Kasir | Batalkan pesanan |
| POST | /api/table-orders/{id}/process-payment | Kasir | Proses bayar cash |
| GET | /api/tables | Kasir/Owner | Daftar meja + status |
| POST | /api/tables/{id}/regenerate-qr | Owner | Buat ulang QR meja |
| POST | /api/webhooks/ipaymu-order | iPaymu | Notifikasi bayar cashless |
