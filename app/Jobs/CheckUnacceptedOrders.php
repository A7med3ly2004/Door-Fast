<?php

namespace App\Jobs;

use App\Events\AdminNotificationCreated;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckUnacceptedOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function handle(): void
    {
        $yMins = (int) Setting::get('notif_unaccepted_mins', 20);
        $cutoff = now()->subMinutes($yMins);
        
        $orders = Order::where('status', 'pending')
            ->where('sent_to_delivery_at', '<=', $cutoff)
            ->whereDoesntHave('adminNotifications', function($q) {
                $q->where('type', 'unaccepted');
            })
            ->select('id', 'order_number', 'sent_to_delivery_at')
            ->get();
            
        foreach($orders as $order) {
            $notif = AdminNotification::create([
                'type' => 'unaccepted',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => "الطلب #{$order->order_number} معلق عند المندوب ولم يتم قبوله"
            ]);
            event(new AdminNotificationCreated($notif));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Job failed permanently: ' . static::class, [
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);
    }
}
