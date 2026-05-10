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

    protected function afterAccept(Order $order, mixed $delivery): void
    {
        ActivityLog::log(
            event: 'order.accepted',
            description: 'تم قبول طلب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['order_number' => $order->order_number],
            causerId: $delivery->id
        );
    }

    protected function afterDeliver(Order $order, mixed $delivery): void
    {
        ActivityLog::log(
            event: 'order.delivered',
            description: 'تم توصيل طلب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['order_number' => $order->order_number, 'total' => $order->total],
            causerId: $delivery->id
        );
    }

    protected function afterCancel(Order $order, Request $request, mixed $delivery): void
    {
        ActivityLog::log(
            event: 'order.cancelled_delivery',
            description: 'تم إلغاء طلب من المندوب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['reason' => $request->reason ?? null],
            causerId: $delivery->id
        );
    }
}
