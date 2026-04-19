<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
                'html' => view('reserve_delivery.orders.partials.new_content')->render(),
                'title' => 'طلبات جديدة',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('reserve_delivery.orders.new');
    }

    public function newData()
    {
        $reserveDelay = (int) Setting::get('reserve_delay_minutes', 5);
        $visibleFrom = now()->subMinutes($reserveDelay);

        $orders = Order::with(['items.shop', 'client'])
            ->where('status', 'pending')
            ->whereNull('delivery_id')
            ->where('sent_to_delivery_at', '<=', $visibleFrom)
            ->whereDate('created_at', today())
            ->orderBy('sent_to_delivery_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function accept($id)
    {

        try {
            DB::transaction(function () use ($id) {
                $delivery = auth()->user();
                $order = Order::where('id', $id)
                    ->where('status', 'pending')
                    ->whereNull('delivery_id')
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
                    'action' => 'تم قبول الطلب من الدلفري الاحتياطي'
                ]);

                event(new OrderStatusUpdated($order));
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
                'html' => view('reserve_delivery.orders.partials.received_content')->render(),
                'title' => 'الطلبات المستلمة',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('reserve_delivery.orders.received');
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
            DB::transaction(function () use ($id) {
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

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => $delivery->id,
                    'action' => 'تم توصيل الطلب'
                ]);

                event(new OrderStatusUpdated($order));
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
            DB::transaction(function () use ($request, $id) {
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
                'html' => view('reserve_delivery.orders.partials.delivered_content')->render(),
                'title' => 'تم التوصيل',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('reserve_delivery.orders.delivered');
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
