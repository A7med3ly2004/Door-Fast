<?php

namespace App\Http\Controllers\Api\Reserve;

use App\Http\Controllers\Api\Delivery\OrderController as DeliveryOrderController;
use App\Events\OrderStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends DeliveryOrderController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/reserve/orders/new
    //
    // الفرق عن الأساسي:
    //  1. delivery_id IS NULL فقط (الاحتياطي لا يرى طلبات محددة له)
    //  2. sent_to_delivery_at <= now() - reserve_delay_minutes (نافذة التأخير)
    // ─────────────────────────────────────────────────────────────────────────

    public function newOrders(Request $request)
    {
        $reserveDelay = (int) Setting::get('reserve_delay_minutes', 5);
        $visibleFrom  = now()->subMinutes($reserveDelay);

        $orders = Order::with(['items.shop', 'client'])
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->where('sent_to_delivery_at', '<=', $visibleFrom)
            ->orderBy('sent_to_delivery_at', 'asc')
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/reserve/orders/{id}/accept
    //
    // الفرق عن الأساسي:
    //  - يقبل فقط الطلبات التي delivery_id IS NULL
    //  - لا يسمح بقبول طلب محدد لمندوب بعينه
    // ─────────────────────────────────────────────────────────────────────────

    public function accept(Request $request, $id)
    {
        $delivery  = $request->user();
        $maxActive = (int) Setting::get('max_active_orders', 3);
        [$start, $end] = Setting::businessDayRange();

        $activeCount = Order::where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->whereBetween('accepted_at', [$start, $end])
            ->count();

        if ($activeCount >= $maxActive) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكنك استلام أكثر من {$maxActive} طلبات في نفس الوقت. قم بتوصيل الطلبات الحالية أولاً.",
            ], 422);
        }

        try {
            DB::transaction(function () use ($id, $delivery) {
                $order = Order::where('id', $id)
                    ->where('status', 'pending')
                    ->whereNull('delivery_id') // ← الاحتياطي: غير محدد فقط
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح');
                }

                $order->update([
                    'status'      => 'received',
                    'delivery_id' => $delivery->id,
                    'accepted_at' => Carbon::now(),
                ]);

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id'  => $delivery->id,
                    'action'   => 'تم قبول الطلب من المندوب الاحتياطي (موبايل)',
                ]);

                ActivityLog::log(
                    event:        'order.accepted_reserve',
                    description:  'تم قبول طلب من المندوب الاحتياطي — ' . $order->order_number,
                    subjectType:  'order',
                    subjectId:    $order->id,
                    subjectLabel: $order->order_number,
                    properties:   ['order_number' => $order->order_number],
                    causerId:     $delivery->id
                );

                event(new OrderStatusUpdated($order));
            });

            return response()->json(['success' => true, 'message' => 'تم قبول الطلب']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}