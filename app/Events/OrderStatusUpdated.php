<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;

    /**
     * يقبل Order model أو array — ويولّد payload موحَّد للـ frontend.
     * الشكل المطلوب من Echo listeners: { order_id, status, order_number, delivery_id }
     */
    public function __construct(Order|array $source)
    {
        if ($source instanceof Order) {
            $this->message = [
                'order_id'     => $source->id,
                'status'       => $source->status,
                'order_number' => $source->order_number,
                'delivery_id'  => $source->delivery_id,
            ];
        } else {
            $this->message = $source + [
                'order_id'     => null,
                'status'       => null,
                'order_number' => null,
                'delivery_id'  => null,
            ];
        }
    }

    public function broadcastOn(): array
    {
        return [new Channel('orders')];
    }
}