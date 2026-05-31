<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Base\BaseDeliveryOrderController;
use App\Models\ActivityLog;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends BaseDeliveryOrderController
{
    protected function viewPrefix(): string
    {
        return 'delivery';
    }

    protected function acceptLogAction(): string
    {
        return 'تم قبول الطلب من المندوب';
    }

    /**
     * Delivery sees ALL pending orders (assigned or unassigned) immediately.
     */
    public function newData()
    {
        $delivery = auth()->user();

        $orders = Order::with(['items.shop', 'client'])
            ->where('status', 'pending')
            ->where(function ($q) use ($delivery) {
                $q->whereNull('delivery_id')
                  ->orWhere('delivery_id', $delivery->id);
            })
            ->where('sent_to_delivery_at', '<=', \Carbon\Carbon::now())
            ->orderBy('sent_to_delivery_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Delivery can accept orders assigned to them OR unassigned ones.
     */
    protected function findPendingOrder(int $id, mixed $delivery): ?Order
    {
        return Order::where('id', $id)
            ->where('status', 'pending')
            ->where(function ($q) use ($delivery) {
                $q->whereNull('delivery_id')
                  ->orWhere('delivery_id', $delivery->id);
            })
            ->lockForUpdate()
            ->first();
    }

}
