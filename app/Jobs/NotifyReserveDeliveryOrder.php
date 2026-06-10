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

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(FcmService $fcm): void
    {
        // ✅ أُزيل whereNull('delivery_id') — نحتاج نجلب الطلب أولاً للتحقق من is_delivery_chosen
        // ✅ الطلب المخصص status = 'received'، غير المخصص status = 'pending'
        $order = Order::where('id', $this->orderId)
            ->whereIn('status', ['pending', 'received'])
            ->first();

        if (!$order) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} no longer pending — skipped.");
            return;
        }

        // ✅ لو الطلب موجّه لمندوب محدد — NotifyPrimaryDeliveryOrder اتكفّل بالإشعار
        // نتجاهل هنا لمنع إشعار مكرر
        if ($order->is_delivery_chosen && $order->delivery_id) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} is assigned to delivery #{$order->delivery_id} — skipped.");
            return;
        }

        // بدون مندوب محدد — broadcast لكل الدليفري الاحتياطي
        event(new ReserveOrderReady($this->orderId));

        $deliveries = User::where('role', 'reserve_delivery')
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($deliveries as $delivery) {
            $fcm->sendToToken(
                $delivery->fcm_token,
                'طلب جديد 🛵',
                'رقم الطلب: ' . $order->order_number,
                [
                    'type' => 'reserve_new_order',
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        }
        Log::info("NotifyReserveDelivery: FCM sent to {$deliveries->count()} reserve delivery(s) for order #{$this->orderId}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyReserveDeliveryOrder failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}