<?php

namespace App\Jobs;

use App\Events\ReserveOrderReady;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyReserveDeliveryOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        // تحقق إن الطلب لسه pending وغير مسند لحد
        // لو اتقبل أو اتلغى → مفيش داعي للإشعار
        $order = Order::where('id', $this->orderId)
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->first();

        if (!$order) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} no longer pending — skipped.");
            return;
        }

        event(new ReserveOrderReady($this->orderId));

        Log::info("NotifyReserveDelivery: fired reserve_new_order for order #{$this->orderId}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyReserveDeliveryOrder failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}