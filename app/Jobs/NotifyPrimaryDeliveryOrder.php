<?php

namespace App\Jobs;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyPrimaryDeliveryOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    // منع تكرار إشعار نفس الطلب لمدة 30 دقيقة على الأكثر
    public int $uniqueFor = 1800;

    public function __construct(public readonly int $orderId)
    {
    }

    public function uniqueId(): string
    {
        return 'notify-primary-order-' . $this->orderId;
    }

    public function handle(FcmService $fcm): void
    {
        $order = Order::where('id', $this->orderId)
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->first();

        if (!$order) {
            Log::info("NotifyPrimaryDelivery: order #{$this->orderId} no longer pending — skipped.");
            return;
        }

        // 1) Pusher — موجود بالفعل، لم يتغير
        event(new OrderStatusUpdated([
            'order_id' => $order->id,
            'status' => 'pending',
            'order_number' => $order->order_number,
            'delivery_id' => null,
        ]));

        // لو الطلب موجّه لدليفري معين
        if ($order->is_delivery_chosen && $order->assigned_delivery_id) {
            $delivery = User::find($order->assigned_delivery_id);
            
            if ($delivery && $delivery->fcm_token) {
                $fcm->sendToToken(
                    $delivery->fcm_token,
                    'الطلب مرسل إليك 🎯',   // ✅ title مختلف
                    'رقم الطلب: ' . $order->order_number,
                    [
                        'type'         => 'assigned_order',
                        'order_id'     => (string) $order->id,
                        'order_number' => $order->order_number,
                    ]
                );
                Log::info("NotifyPrimaryDelivery: fired for order #{$this->orderId}, FCM sent to 1 assigned delivery");
            } else {
                Log::info("NotifyPrimaryDelivery: fired for order #{$this->orderId}, but assigned delivery not found or no FCM");
            }
        } else {
            // broadcast لكل الدليفري
            $deliveries = User::where('role', 'delivery')
                ->where('is_active', true)
                ->whereNotNull('fcm_token')
                ->get();
                
            foreach ($deliveries as $delivery) {
                $fcm->sendToToken(
                    $delivery->fcm_token,
                    'طلب جديد 🛵',
                    'رقم الطلب: ' . $order->order_number,
                    [
                        'type'         => 'new_order',
                        'order_id'     => (string) $order->id,
                        'order_number' => $order->order_number,
                    ]
                );
            }
            Log::info("NotifyPrimaryDelivery: fired for order #{$this->orderId}, FCM sent to {$deliveries->count()} delivery(s)");
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyPrimaryDeliveryOrder failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}