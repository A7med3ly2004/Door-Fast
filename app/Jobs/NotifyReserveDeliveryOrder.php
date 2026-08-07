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
        // ✅ Guard 1: تحقق من flag الـ Cache — يُضبط فور قبول الطلب من أي مندوب
        if (\Illuminate\Support\Facades\Cache::has('order_accepted_' . $this->orderId)) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} already accepted by primary — skipped (cache flag).");
            return;
        }

        // ✅ Guard 2: الطلب يجب أن يكون pending وبدون delivery_id
        $order = Order::where('id', $this->orderId)
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->first();

        if (!$order) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} no longer pending/unassigned — skipped.");
            return;
        }

        // ✅ Cache Guard: منع الإشعار المكرر عند retry أو تعدد الـ Jobs
        $cacheKey = 'order_reserve_notified_' . $this->orderId;
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::info("NotifyReserveDelivery: order #{$this->orderId} already notified — skipped.");
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(60));

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