<?php

namespace App\Jobs;

use App\Events\AdminNotificationCreated;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDelayedDeliveryOrders implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public int $uniqueFor = 60;

    public function uniqueId(): string
    {
        return 'check-delayed-delivery-orders';
    }

    public function handle(): void
    {
        $xMins = (int) Setting::get('notif_delay_delivery_mins', 20);
        $cutoff = now()->subMinutes($xMins);

        $orders = Order::where('status', 'received')
            ->where('accepted_at', '<=', $cutoff)
            ->whereDoesntHave('adminNotifications', function ($q) {
                $q->where('type', 'delayed_delivery');
            })
            ->with('delivery:id,name')
            ->select('id', 'order_number', 'accepted_at', 'delivery_id')
            ->get();

        foreach ($orders as $order) {
            $deliveryName = $order->delivery ? $order->delivery->name : 'غير محدد';
            $notif = AdminNotification::create([
                'type' => 'delayed_delivery',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => "طلب #{$order->order_number} تأخر عن التوصيل مع المندوب {$deliveryName}"
            ]);
            event(new AdminNotificationCreated($notif));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Job failed permanently: ' . static::class, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
