<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Base\BaseDeliveryOrderController;
use App\Models\Order;
use App\Models\Setting;

class OrderController extends BaseDeliveryOrderController
{
    protected function viewPrefix(): string
    {
        return 'reserve_delivery';
    }

    protected function acceptLogAction(): string
    {
        return 'تم قبول الطلب من المندوب الاحتياطي';
    }

    /**
     * Reserve delivery sees only UNASSIGNED orders after a delay window.
     */
    public function newData()
    {
        $reserveDelay = (int) Setting::get('reserve_delay_minutes', 5);
        $visibleFrom  = now()->subMinutes($reserveDelay);

        $orders = Order::with(['items.shop', 'client'])
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->where('sent_to_delivery_at', '<=', $visibleFrom)
            ->orderBy('sent_to_delivery_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Reserve delivery can only accept UNASSIGNED orders.
     */
    protected function findPendingOrder(int $id, mixed $delivery): ?Order
    {
        return Order::where('id', $id)
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->lockForUpdate()
            ->first();
    }
}
