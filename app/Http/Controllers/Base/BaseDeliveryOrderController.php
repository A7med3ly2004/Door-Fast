<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Setting;
use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

abstract class BaseDeliveryOrderController extends Controller
{
    /**
     * The view prefix for this role (e.g. 'delivery' or 'reserve_delivery')
     */
    abstract protected function viewPrefix(): string;

    /**
     * Log message when accepting an order (differs per role)
     */
    protected function acceptLogAction(): string
    {
        return 'تم قبول الطلب من المندوب';
    }

    /**
     * Hook called after a successful accept — override to add extra logging/events
     */
    protected function afterAccept(Order $order, mixed $delivery): void
    {
        \App\Models\ActivityLog::log(
            event: 'order.accepted',
            description: 'تم قبول طلب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['order_number' => $order->order_number],
            causerId: $delivery->id
        );
    }

    /**
     * Hook called after a successful deliver — override to add extra logging/events
     */
    protected function afterDeliver(Order $order, mixed $delivery): ?\App\Models\ActivityLog
    {
        return \App\Models\ActivityLog::log(
            event: 'order.delivered',
            description: 'تم توصيل طلب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['order_number' => $order->order_number, 'total' => $order->total],
            causerId: $delivery->id
        );
    }

    /**
     * Hook called after a successful cancel — override to add extra logging/events
     */
    protected function afterCancel(Order $order, Request $request, mixed $delivery): void
    {
        \App\Models\ActivityLog::log(
            event: 'order.cancelled_delivery',
            description: 'تم إلغاء طلب من المندوب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['reason' => $request->reason ?? null],
            causerId: $delivery->id
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared methods
    // ─────────────────────────────────────────────────────────────────────────

    public function newOrders()
    {
        $prefix = $this->viewPrefix();
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view("{$prefix}.orders.partials.new_content")->render(),
                'title'      => 'طلبات جديدة',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view("{$prefix}.orders.new");
    }

    public function received()
    {
        $prefix = $this->viewPrefix();
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view("{$prefix}.orders.partials.received_content")->render(),
                'title'      => 'الطلبات المستلمة',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view("{$prefix}.orders.received");
    }

    public function receivedData()
    {
        $delivery = auth()->user();

        $orders = Order::with(['items.shop', 'client'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->orderBy('accepted_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function accept($id)
    {
        $maxActive = (int) Setting::get('max_active_orders', 3);
        list($startOfToday, $endOfToday) = Setting::businessDayRange();

        $activeCount = Order::where('delivery_id', auth()->id())
            ->where('status', 'received')
            ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
            ->count();

        if ($activeCount >= $maxActive) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكنك استلام أكثر من {$maxActive} طلبات في نفس الوقت. قم بتوصيل الطلبات الحالية أولاً.",
            ]);
        }

        try {
            DB::transaction(function () use ($id) {
                $delivery = auth()->user();
                $order    = $this->findPendingOrder($id, $delivery);

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
                    'action'   => $this->acceptLogAction(),
                ]);

                $this->afterAccept($order, $delivery);
                event(new OrderStatusUpdated($order));
            });

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Find a pending order eligible for acceptance — override to customise the query
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

    public function deliver($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $delivery = auth()->user();
                $order    = Order::where('id', $id)
                    ->where('delivery_id', $delivery->id)
                    ->where('status', 'received')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح أو تم تغيير حالته');
                }

                $order->update([
                    'status'       => 'delivered',
                    'delivered_at' => Carbon::now(),
                ]);

                $walletTx = null;
                // تسجيل رسوم التوصيل في خزينة المندوب
                if ($order->delivery_fee > 0) {
                    $wallet = $delivery->getOrCreateWallet();
                    $walletTx = app(\App\Services\WalletService::class)->credit(
                        wallet:      $wallet,
                        amount:      (float) $order->delivery_fee,
                        type:        'delivery_fee_received',
                        description: 'رسوم توصيل — طلب ' . $order->order_number,
                        createdBy:   $delivery->id,
                        orderId:     $order->id,
                        date:        \App\Models\Setting::currentBusinessDate()
                    );
                }

                $discountTx = null;
                // خصم الخصم الممنوح للعميل من محفظة المندوب
                if ($order->discount > 0) {
                    $wallet = $delivery->getOrCreateWallet();
                    $discountTx = app(\App\Services\WalletService::class)->debit(
                        wallet:      $wallet,
                        amount:      (float) $order->discount,
                        type:        'discount',
                        description: 'خصم من الرصيد مقابل خصم للعميل — طلب ' . $order->order_number,
                        createdBy:   $delivery->id,
                        orderId:     $order->id,
                        date:        \App\Models\Setting::currentBusinessDate()
                    );
                }

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id'  => $delivery->id,
                    'action'   => 'تم توصيل الطلب',
                ]);

                // داخل المعاملة: إذا فشلت إعادة احتساب أرباح اليوم،
                // يتم rollback لكل ما سبق (الحالة، المحفظة، السجل).
                app(\App\Services\DeliveryProfitService::class)
                    ->recalculateDayProfits($delivery, $order);

                // إعادة حساب أرباح الكول سنتر الذي أنشأ الطلب (إن وُجد)
                if ($order->callcenter_id) {
                    $callcenter = \App\Models\User::find($order->callcenter_id);
                    if ($callcenter) {
                        app(\App\Services\CallcenterProfitService::class)
                            ->recalculateDayProfits($callcenter, $order);
                    }
                }

                $log = $this->afterDeliver($order, $delivery);
                
                if ($log) {
                    if (isset($walletTx)) {
                        $walletTx->update(['log_id' => $log->id]);
                    }
                    if (isset($discountTx)) {
                        $discountTx->update(['log_id' => $log->id]);
                    }
                }

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
                $order    = Order::where('id', $id)
                    ->where('delivery_id', $delivery->id)
                    ->where('status', 'received')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح أو تم تغيير حالته');
                }

                $order->update(['status' => 'cancelled']);

                $notif = \App\Models\AdminNotification::create([
                    'type'         => 'cancelled',
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'message'      => "تم إلغاء الطلب #{$order->order_number} بواسطة مندوب : {$delivery->name}",
                    'audience'     => 'all', // يظهر للأدمن والكول سنتر
                ]);
                event(new \App\Events\AdminNotificationCreated($notif));

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id'  => $delivery->id,
                    'action'   => 'تم إلغاء الطلب من المندوب: ' . $request->reason,
                ]);

                $this->afterCancel($order, $request, $delivery);
                event(new OrderStatusUpdated($order));
            });

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delivered()
    {
        $prefix = $this->viewPrefix();
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view("{$prefix}.orders.partials.delivered_content")->render(),
                'title'      => 'تم التوصيل',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view("{$prefix}.orders.delivered");
    }

    public function deliveredData()
    {
        $delivery = auth()->user();
        list($startOfToday, $endOfToday) = Setting::businessDayRange();

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
        $order    = Order::with(['items.shop', 'client', 'recipientClient'])
            ->where('id', $id)
            ->where('delivery_id', $delivery->id)
            ->firstOrFail();

        $pdf = \PDF::loadView('invoices.order', compact('order'));
        return $pdf->download($order->order_number . '.pdf');
    }
}
