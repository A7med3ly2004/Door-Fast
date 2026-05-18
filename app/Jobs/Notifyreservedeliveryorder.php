<?php

namespace App\Jobs;

use App\Events\ReserveOrderReady;
use App\Models\Order;
use App\Models\User;
use App\Services\FcmService;
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

    public function handle(FcmService $fcm): void
    {
        $order = Order::where('id', $this->orderId)
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->first();

        if (!$order) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} no longer pending — skipped.");
            return;
        }

        // 1) Pusher — موجود بالفعل، لم يتغير
        event(new ReserveOrderReady($this->orderId));

        // 2) FCM — جديد
        $deliveries = User::where('role', 'reserve_delivery')
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($deliveries as $delivery) {
            $fcm->sendToToken(
                $delivery->fcm_token,
                'طلب احتياطي جديد 🛵',
                'رقم الطلب: ' . $order->order_number,
                [
                    'type'         => 'reserve_new_order',
                    'order_id'     => (string) $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        }

        Log::info("NotifyReserveDelivery: fired for order #{$this->orderId}, FCM sent to {$deliveries->count()} delivery(s)");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyReserveDeliveryOrder failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}