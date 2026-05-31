<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Setting;
use App\Events\OrderStatusUpdated;
use App\Events\AdminNotificationCreated;
use App\Services\WalletService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/delivery/orders/new
    // ─────────────────────────────────────────────────────────────────────────

    public function newOrders(Request $request)
    {
        $delivery = $request->user();

        $orders = Order::with(['items.shop', 'client', 'recipientClient'])
            ->where('status', 'pending')
            ->where('sent_to_delivery_at', '<=', Carbon::now())
            ->where(function ($q) use ($delivery) {
                $q->whereNull('delivery_id')
                    ->orWhere('delivery_id', $delivery->id);
            })
            ->orderBy('sent_to_delivery_at', 'asc')
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/delivery/orders/received
    // ─────────────────────────────────────────────────────────────────────────

    public function received(Request $request)
    {
        $delivery = $request->user();

        $orders = Order::with(['items.shop', 'client', 'recipientClient'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->orderBy('accepted_at', 'asc')
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/delivery/orders/delivered
    // ─────────────────────────────────────────────────────────────────────────

    public function delivered(Request $request)
    {
        $delivery = $request->user();
        [$start, $end] = Setting::businessDayRange();

        $orders = Order::with(['items.shop', 'client', 'recipientClient'])
            ->where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$start, $end])
            ->orderBy('delivered_at', 'desc')
            ->get()
            ->map(fn($o) => $this->formatOrder($o));

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/delivery/orders/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Request $request, $id)
    {
        $delivery = $request->user();

        $order = Order::with(['items.shop', 'client', 'recipientClient'])
            ->where('id', $id)
            ->where(function ($q) use ($delivery) {
                $q->where('delivery_id', $delivery->id)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('delivery_id')
                            ->where('status', 'pending')
                            ->where('sent_to_delivery_at', '<=', Carbon::now());
                    });
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json(['success' => true, 'order' => $this->formatOrder($order)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/delivery/orders/{id}/accept
    // ─────────────────────────────────────────────────────────────────────────

    public function accept(Request $request, $id)
    {
        $delivery = $request->user();
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
                    ->where(function ($q) use ($delivery) {
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
                    'accepted_at' => Carbon::now(),
                ]);

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => $delivery->id,
                    'action' => 'تم قبول الطلب من المندوب (موبايل)',
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
            });

            return response()->json(['success' => true, 'message' => 'تم قبول الطلب']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/delivery/orders/{id}/deliver
    // ─────────────────────────────────────────────────────────────────────────

    public function deliver(Request $request, $id)
    {
        $delivery = $request->user();

        $deliveredOrder = null;

        try {
            DB::transaction(function () use ($id, $delivery, &$deliveredOrder) {
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
                    'delivered_at' => Carbon::now(),
                ]);

                $deliveredOrder = $order;

                $walletTx = null;
                if ($order->delivery_fee > 0) {
                    $wallet = $delivery->getOrCreateWallet();
                    $walletTx = app(WalletService::class)->credit(
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
                    'action' => 'تم توصيل الطلب (موبايل)',
                ]);

                $log = ActivityLog::log(
                    event: 'order.delivered',
                    description: 'تم توصيل طلب — ' . $order->order_number,
                    subjectType: 'order',
                    subjectId: $order->id,
                    subjectLabel: $order->order_number,
                    properties: ['order_number' => $order->order_number, 'total' => $order->total],
                    causerId: $delivery->id
                );

                if ($walletTx && $log) {
                    $walletTx->update(['log_id' => $log->id]);
                }

                event(new OrderStatusUpdated($order));
            });

            if (isset($deliveredOrder)) {
                app(\App\Services\DeliveryProfitService::class)
                    ->recalculateDayProfits($delivery, $deliveredOrder);
            }

            return response()->json(['success' => true, 'message' => 'تم توصيل الطلب بنجاح']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/delivery/orders/{id}/cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $delivery = $request->user();

        try {
            DB::transaction(function () use ($request, $id, $delivery) {
                $order = Order::where('id', $id)
                    ->where('delivery_id', $delivery->id)
                    ->where('status', 'received')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new Exception('الطلب غير متاح أو تم تغيير حالته');
                }

                $order->update(['status' => 'cancelled']);

                $notif = AdminNotification::create([
                    'type' => 'cancelled',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'message' => "تم إلغاء الطلب #{$order->order_number} — {$request->reason}",
                ]);
                event(new AdminNotificationCreated($notif));

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => $delivery->id,
                    'action' => 'تم إلغاء الطلب من المندوب (موبايل): ' . $request->reason,
                ]);

                ActivityLog::log(
                    event: 'order.cancelled_delivery',
                    description: 'تم إلغاء طلب من المندوب — ' . $order->order_number,
                    subjectType: 'order',
                    subjectId: $order->id,
                    subjectLabel: $order->order_number,
                    properties: ['order_number' => $order->order_number, 'reason' => $request->reason],
                    causerId: $delivery->id
                );

                event(new OrderStatusUpdated($order));
            });

            return response()->json(['success' => true, 'message' => 'تم إلغاء الطلب']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helper: format order for API response
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/delivery/orders/{id}/invoice
     */
    public function downloadInvoice($id)
    {
        $delivery = auth()->user();
        $order = Order::with(['items.shop', 'client', 'recipientClient'])
            ->where('id', $id)
            ->where('delivery_id', $delivery->id)
            ->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.order', compact('order'))->setPaper('a4', 'portrait');
        return $pdf->download($order->order_number . '.pdf');
    }

    protected function formatOrder(Order $order): array
    {
        $client = $order->client;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total' => (float) $order->total,
            'delivery_fee' => (float) $order->delivery_fee,
            'discount' => (float) $order->discount,
            'discount_type' => $order->discount_type,
            'notes' => $order->notes,
            'is_delivery_chosen' => $order->is_delivery_chosen,
            'sent_to_delivery_at' => $order->sent_to_delivery_at?->toIso8601String(),
            'accepted_at' => $order->accepted_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'phone2' => $client->phone2 ?? null,
                'address' => $order->client_address,
                'delivery_link' => $order->client_delivery_link,
            ] : null,
            'send_to' => $order->send_to_phone ? [
                'name' => $order->recipientClient?->name ?? null,
                'phone' => $order->send_to_phone,
                'phone2' => $order->send_to_phone2 ?? null,
                'address' => $order->send_to_address,
                'delivery_link' => $order->send_to_delivery_link,
            ] : null,
            'items' => $order->items->map(fn($item) => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'shop' => $item->shop ? [
                    'id' => $item->shop->id,
                    'name' => $item->shop->name,
                ] : null,
            ])->all(),
        ];
    }
}
