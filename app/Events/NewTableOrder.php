<?php

namespace App\Events;

use App\Models\TableOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTableOrder implements ShouldBroadcast
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
