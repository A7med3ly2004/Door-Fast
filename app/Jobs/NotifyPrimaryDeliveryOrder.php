<?php

namespace App\Jobs;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyPrimaryDeliveryOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(): void
    {
        // تحقق إن الطلب لا يزال pending وبدون مندوب
        $order = Order::where('id', $this->orderId)
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->first();

        if (!$order) {
            Log::info("NotifyPrimaryDelivery: order #{$this->orderId} no longer pending — skipped.");
            return;
        }

        event(new OrderStatusUpdated([
            'order_id' => $order->id,
            'status' => 'pending',
            'order_number' => $order->order_number,
            'delivery_id' => null,
        ]));

        Log::info("NotifyPrimaryDelivery: fired order_updated for order #{$this->orderId}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyPrimaryDeliveryOrder failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}