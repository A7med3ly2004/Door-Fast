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
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();

        $orders = Order::with(['items.shop', 'client'])
            ->where('status', 'pending')
            ->where(function($q) use ($delivery) {
                $q->whereNull('delivery_id')
                  ->orWhere('delivery_id', $delivery->id);
            })
            ->where('sent_to_delivery_at', '<=', Carbon::now())
            ->where(function($q) use ($startOfToday, $endOfToday) {
                $q->whereBetween('sent_to_delivery_at', [$startOfToday, $endOfToday])
                  ->orWhereBetween('created_at', [$startOfToday, $endOfToday]);
            })
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function accept($id)
    {

        $maxActive = (int) Setting::get('max_active_orders', 3);
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $activeCount = Order::where('delivery_id', auth()->id())
            ->where('status', 'received')
            ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
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
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();

        $orders = Order::with(['items.shop', 'client'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
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

                // تسجيل رسوم التوصيل في خزينة المندوب
                if ($order->delivery_fee > 0) {
                    $wallet = $delivery->getOrCreateWallet();
                    app(\App\Services\WalletService::class)->credit(
                        wallet: $wallet,
                        amount: (float) $order->delivery_fee,
                        type: 'delivery_fee_received',
                        description: 'رسوم توصيل — طلب ' . $order->order_number,
                        createdBy: $delivery->id,
                        orderId: $order->id,
                        date: now()->toDateString()
                    );
                }

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

                $notif = \App\Models\AdminNotification::create([
                    'type'         => 'cancelled',
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'message'      => "تم إلغاء الطلب #{$order->order_number}",
                ]);
                event(new \App\Events\AdminNotificationCreated($notif));

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
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();

        $orders = Order::with(['items.shop', 'client'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$startOfToday, $endOfToday])
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function downloadInvoice($id)
    {
        $delivery = auth()->user();
        $order = Order::with(['items.shop', 'client'])
            ->where('id', $id)
            ->where('delivery_id', $delivery->id)
            ->firstOrFail();

        $pdf = \PDF::loadView('invoices.order', compact('order'));
        return $pdf->download($order->order_number . '.pdf');
    }
}
