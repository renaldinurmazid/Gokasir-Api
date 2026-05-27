# GoKasir – Realtime Toast ke Flutter via Laravel Reverb + WebSocket
 
> Stack: **Laravel Reverb** (WebSocket server) + **Flutter** (`web_socket_channel`)
> Trigger: Pesanan masuk dari web order → toast + bunyi di app Flutter kasir
> Syarat: App Flutter **harus aktif / foreground**
 
---
 
## Gambaran Alur
 
```
[Customer Web Order]
        │
        │ POST /api/public/order/{tableCode}/place
        ▼
[Laravel — PublicOrderController]
        │
        │ TableOrder dibuat (status: pending)
        │ event(new NewTableOrder($order))  ──────────────────────►  [Laravel Reverb]
        │                                                                    │
        │                                                         broadcast ke channel:
        │                                                         store.{store_id}
        │                                                                    │
        ▼                                                                    ▼
[Laravel selesai]                                              [Flutter — WebSocket]
                                                                      │
                                                              terima payload order
                                                                      │
                                                              tampil Toast + bunyi 🔔
```
 
---
 
## Langkah 1 — Install & Setup Laravel Reverb
 
### 1.1 Install Reverb
 
```bash
composer require laravel/reverb
php artisan reverb:install
```
 
Pilih **yes** semua saat ditanya, Reverb akan generate config dan update `.env`.
 
### 1.2 `.env` — konfigurasi Reverb
 
```env
BROADCAST_CONNECTION=reverb
 
REVERB_APP_ID=gokasir
REVERB_APP_KEY=gokasir-key-rahasia
REVERB_APP_SECRET=gokasir-secret-rahasia
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
 
# Untuk Flutter konek dari luar (pakai IP server / domain)
REVERB_SERVER_HOST=192.168.1.100   # IP server / domain production
REVERB_SERVER_PORT=8080
```
 
> **Production:** ganti `REVERB_SCHEME=https` dan gunakan domain
> dengan reverse proxy (Nginx) ke port 8080.
 
### 1.3 Aktifkan BroadcastServiceProvider
 
```php
// bootstrap/providers.php — pastikan ada
App\Providers\BroadcastServiceProvider::class,
```
 
### 1.4 Jalankan Reverb
 
```bash
# Development
php artisan reverb:start
 
# Production (pakai supervisor)
php artisan reverb:start --host=0.0.0.0 --port=8080 --daemon
```
 
---
 
## Langkah 2 — Buat Event Laravel
 
### 2.1 Buat Event
 
```bash
php artisan make:event NewTableOrder
```
 
### 2.2 Isi Event
 
```php
// app/Events/NewTableOrder.php
namespace App\Events;
 
use App\Models\TableOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
 
class NewTableOrder implements ShouldBroadcastNow
// ShouldBroadcastNow = langsung broadcast tanpa queue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    public function __construct(public TableOrder $order) {}
 
    /**
     * Channel yang dituju: store.{store_id}
     * Flutter subscribe ke channel ini berdasarkan store_id kasir login
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('store.' . $this->order->store_id),
        ];
    }
 
    /**
     * Nama event yang diterima Flutter
     */
    public function broadcastAs(): string
    {
        return 'new.order';
    }
 
    /**
     * Data yang dikirim ke Flutter
     */
    public function broadcastWith(): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'table_name'   => $this->order->table->name,
            'grand_total'  => $this->order->grand_total,
            'item_count'   => $this->order->items()->count(),
            'notes'        => $this->order->notes,
            'created_at'   => $this->order->created_at->format('H:i'),
            'items'        => $this->order->items->map(fn($i) => [
                'name' => $i->product->name,
                'qty'  => $i->qty,
            ]),
        ];
    }
}
```
 
### 2.3 Dispatch Event dari Controller
 
Tambahkan 1 baris di `PublicOrderController::placeOrder()` setelah order dibuat:
 
```php
// app/Http/Controllers/Api/PublicOrderController.php
 
// Setelah TableOrder::create(...) dan items disimpan:
 
// ── Broadcast ke Flutter kasir ────────────────────────────────────
event(new \App\Events\NewTableOrder($order->load('table', 'items.product')));
// ─────────────────────────────────────────────────────────────────
```
 
---
 
## Langkah 3 — Konfigurasi Channel (Opsional Auth)
 
Karena channel yang dipakai adalah **public channel** (`Channel`, bukan `PrivateChannel`),
Flutter tidak perlu auth token untuk subscribe. Cocok untuk kesederhanaan.
 
```php
// routes/channels.php
// Tidak perlu tambahan apapun untuk public channel
// Jika ingin private (lebih aman), ganti ke PrivateChannel dan tambahkan:
 
Broadcast::channel('store.{storeId}', function ($user, $storeId) {
    return (int) $user->store_id === (int) $storeId
        || $user->role === 'owner';
});
```