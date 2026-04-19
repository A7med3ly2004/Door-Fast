<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Setting;
use App\Models\OrderLog;
use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderController extends Controller
{

    public function newOrders()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('delivery.orders.partials.new_content')->render(),
                'title'      => 'طلبات جديدة',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('delivery.orders.new');
    }

    public function newData()
    {
        $delivery = auth()->user();
        $today = Carbon::today();

        $orders = Order::with(['items.shop', 'client'])
            ->where('status', 'pending')
            ->where(function($q) use ($delivery) {
                $q->whereNull('delivery_id')
                  ->orWhere('delivery_id', $delivery->id);
            })
            ->where('sent_to_delivery_at', '<=', Carbon::now())
            ->where(function($q) use ($today) {
                $q->whereDate('sent_to_delivery_at', $today)
                  ->orWhereDate('created_at', $today);
            })
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function accept($id)
    {

        $maxUnsettled = (float) Setting::get('max_unsettled_limit', 500);
        if (auth()->user()->unsettled_value >= $maxUnsettled) {
            return response()->json(['success' => false, 'message' => "تم تجاوز الحد الأقصى للعهدة ({$maxUnsettled} جنيه) — برجاء التواصل مع الإدارة وتسوية العهدة للتمكن من استلام طلبات جديدة"]);
        }

        $maxActive = (int) Setting::get('max_active_orders', 3);
        $activeCount = Order::where('delivery_id', auth()->id())
            ->where('status', 'received')
            ->whereDate('accepted_at', Carbon::today())
            ->count();

        if ($activeCount >= $maxActive) {
            return response()->json(['success' => false, 'message' => "لا يمكنك استلام أكثر من {$maxActive} طلبات في نفس الوقت. قم بتوصيل الطلبات الحالية أولاً."]);
        }

        try {
            DB::transaction(function() use ($id) {
                $delivery = auth()->user();
                $order = Order::where('id', $id)
                    ->where('status', 'pending')
                    ->where(function($q) use ($delivery) {
                        $q->whereNull('delivery_id')
                          ->orWhere('delivery_id', $delivery->id);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح');
                }

                $order->update([
                    'status' => 'received',
                    'delivery_id' => $delivery->id,
                    'accepted_at' => Carbon::now()
                ]);

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => $delivery->id,
                    'action' => 'تم قبول الطلب من الدلفري'
                ]);

                ActivityLog::log(
                    event: 'order.accepted',
                    description: 'تم قبول طلب — ' . $order->order_number,
                    subjectType: 'order',
                    subjectId: $order->id,
                    subjectLabel: $order->order_number,
                    properties: ['order_number' => $order->order_number],
                    causerId: $delivery->id
                );

                event(new OrderStatusUpdated($order));
                // TODO: SmsService::orderAccepted($order);
            });

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function received()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('delivery.orders.partials.received_content')->render(),
                'title'      => 'الطلبات المستلمة',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('delivery.orders.received');
    }

    public function receivedData()
    {
        $delivery = auth()->user();
        $today = Carbon::today();

        $orders = Order::with(['items.shop', 'client'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->whereDate('accepted_at', $today)
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function deliver($id)
    {
        try {
            DB::transaction(function() use ($id) {
                $delivery = auth()->user();
                $order = Order::where('id', $id)
                    ->where('delivery_id', $delivery->id)
                    ->where('status', 'received')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح أو تم تغيير حالته');
                }

                $order->update([
                    'status' => 'delivered',
                    'delivered_at' => Carbon::now()
                ]);

                $delivery->increment('unsettled_value', $order->total);
                $delivery->increment('unsettled_fees', $order->delivery_fee);

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => $delivery->id,
                    'action' => 'تم توصيل الطلب'
                ]);

                ActivityLog::log(
                    event: 'order.delivered',
                    description: 'تم توصيل طلب — ' . $order->order_number,
                    subjectType: 'order',
                    subjectId: $order->id,
                    subjectLabel: $order->order_number,
                    properties: ['order_number' => $order->order_number, 'total' => $order->total],
                    causerId: $delivery->id
                );

                event(new OrderStatusUpdated($order));
                // TODO: SmsService::orderDelivered($order);
            });

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);

        try {
            DB::transaction(function() use ($request, $id) {
                $delivery = auth()->user();
                $order = Order::where('id', $id)
                    ->where('delivery_id', $delivery->id)
                    ->where('status', 'received')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح أو تم تغيير حالته');
                }

                $order->update([
                    'status' => 'cancelled'
                ]);

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => $delivery->id,
                    'action' => 'تم إلغاء الطلب من الدلفري: ' . $request->reason
                ]);

                ActivityLog::log(
                    event: 'order.cancelled_delivery',
                    description: 'تم إلغاء طلب من المندوب — ' . $order->order_number,
                    subjectType: 'order',
                    subjectId: $order->id,
                    subjectLabel: $order->order_number,
                    properties: ['reason' => $request->reason ?? null],
                    causerId: $delivery->id
                );

                event(new OrderStatusUpdated($order));
            });

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delivered()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('delivery.orders.partials.delivered_content')->render(),
                'title'      => 'تم التوصيل',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('delivery.orders.delivered');
    }

    public function deliveredData()
    {
        $delivery = auth()->user();
        $today = Carbon::today();

        $orders = Order::with(['items.shop', 'client'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', $today)
            ->get();

        return response()->json(['orders' => $orders]);
    }
}
