<?php

namespace App\Jobs;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\User;
use App\Services\FcmService;
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

    public function handle(FcmService $fcm): void
    {
        // ✅ أُزيل شرط whereNull('delivery_id') — كان يمنع وصول الإشعار للطلب المخصص
        // ✅ الطلب المخصص status = 'received'، غير المخصص status = 'pending'
        $order = Order::where('id', $this->orderId)
            ->whereIn('status', ['pending', 'received'])
            ->first();

        if (!$order) {
            Log::info("NotifyPrimaryDelivery: order #{$this->orderId} no longer pending — skipped.");
            return;
        }

        // ✅ منع إرسال مكرر: لو سبق وصل إشعار لهذا الطلب (من Job فوري عند sendEarly)
        // هذا الـ Job هو القديم المؤجل — نتجاهل
        $cacheKey = 'order_notified_primary_' . $this->orderId;
        if (\Illuminate\Support\Facades\Cache::has('order_early_sent_' . $this->orderId)
            && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::info("NotifyPrimaryDelivery: order #{$this->orderId} already notified via early-send — skipped.");
            return;
        }
        // سجّل إن هذا الـ Job شتغل
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(60));

        // لو الطلب موجّه لمندوب معين — أبعت له FCM مباشرةً
        // ✅ delivery_id هو الـ column الصح في الـ Model (مش assigned_delivery_id)
        if ($order->is_delivery_chosen && $order->delivery_id) {
            $delivery = User::find($order->delivery_id);

            if ($delivery && $delivery->fcm_token) {
                // Pusher للمندوب المخصص فقط
                event(new OrderStatusUpdated([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'order_number' => $order->order_number,
                    'delivery_id' => $order->delivery_id,
                ]));

                // FCM للمندوب المخصص
                $fcm->sendToToken(
                    $delivery->fcm_token,
                    'الطلب مرسل إليك 🎯',
                    'رقم الطلب: ' . $order->order_number,
                    [
                        'type' => 'assigned_order',
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ]
                );
                Log::info("NotifyPrimaryDelivery: FCM sent to assigned delivery #{$order->delivery_id} for order #{$this->orderId}");
            } else {
                Log::warning("NotifyPrimaryDelivery: assigned delivery #{$order->delivery_id} not found or no FCM token — order #{$this->orderId}");
            }

            return; // انتهى — لا نبعت لباقي الدليفري
        }

        // بدون مندوب محدد — broadcast لكل الدليفري الأساسي
        event(new OrderStatusUpdated([
            'order_id' => $order->id,
            'status' => 'pending',
            'order_number' => $order->order_number,
            'delivery_id' => null,
        ]));

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
                    'type' => 'new_order',
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        }
        Log::info("NotifyPrimaryDelivery: FCM sent to {$deliveries->count()} delivery(s) for order #{$this->orderId}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('NotifyPrimaryDeliveryOrder failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}