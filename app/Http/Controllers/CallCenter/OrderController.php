<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Setting;
use App\Models\Shop;
use App\Events\OrderStatusUpdated;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CC\OrderController
 *
 * المسؤوليات:
 *  1. Validation — هنا فقط.
 *  2. استدعاء OrderService بالـ validated data.
 *  3. بناء الـ Response.
 *
 * store() و AdminOrderController::store() يشتركان في نفس الـ service.
 */
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function create()
    {
        $shops = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('callcenter.orders.partials.create_content', compact('shops'))->render(),
                'title' => 'إنشاء طلب',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('callcenter.orders.create', compact('shops'));
    }

    public function store(Request $request)
    {
        // ── Validation (Controller responsibility) ──────────────────
        $validated = $request->validate([
            'phone' => ['required', 'digits:11'],
            'phone2' => ['nullable', 'digits:11'],
            'code' => 'required|string',
            'name' => 'required|string',
            'client_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.shop_id' => 'required|exists:shops,id',
            'send_to_phone' => ['nullable', 'digits:11'],
            'send_to_phone2' => ['nullable', 'digits:11'],
            'client_delivery_link' => 'nullable|url|max:500',
            'send_to_delivery_link' => 'nullable|url|max:500',
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.digits' => 'رقم الهاتف يجب أن يتكون من 11 رقم',
            'phone2.digits' => 'رقم الهاتف الثاني يجب أن يتكون من 11 رقم',
            'send_to_phone.digits' => 'رقم هاتف المستلم يجب أن يتكون من 11 رقم',
            'send_to_phone2.digits' => 'رقم الهاتف الثاني للمستلم يجب أن يتكون من 11 رقم',
            'code.required' => 'الكود مطلوب',
            'name.required' => 'اسم العميل مطلوب',
            'client_address.required' => 'العنوان مطلوب',
            'items.required' => 'يجب إضافة صنف واحد على الأقل',
            'items.*.shop_id.required' => 'يجب اختيار المتجر لكل صنف',
            'items.*.shop_id.exists' => 'المتجر المختار غير موجود',
        ]);

        // Merge remaining (non-validated) fields the service needs
        $data = array_merge($validated, $request->only([
            'phone2',
            'is_new_address',
            'discount',
            'discount_type',
            'delivery_fee',
            'delivery_id',
            'notes',
            'send_to_phone',
            'send_to_phone2',
            'send_to_address',
            'send_to_name',
            'send_to_code',
            'send_to_client_id',
            'client_delivery_link',
            'send_to_delivery_link',
            'opened_at',
        ]));

        // ── Discount guard (HTTP-layer: return JSON error, not exception) ─
        $items = $data['items'];
        $itemsTotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $deliveryFee = (float) ($data['delivery_fee'] ?? 0);
        $baseTotal = $itemsTotal + $deliveryFee;

        $discount = (float) ($data['discount'] ?? 0);
        $discountType = $data['discount_type'] ?? 'amount';
        $discountAmt = $discountType === 'percent' ? ($baseTotal * $discount / 100) : $discount;
        $maxDiscountPercent = (float) Setting::get('max_discount_percentage', 50);

        if ($baseTotal > 0 && ($discountAmt / $baseTotal * 100) > $maxDiscountPercent) {
            return response()->json([
                'errors' => ['discount' => ["عذراً، نسبة الخصم لا يمكن أن تتجاوز {$maxDiscountPercent}% من إجمالي الطلب. (أقصى قيمة: " . round($baseTotal * $maxDiscountPercent / 100, 2) . " ج)"]],
            ], 422);
        }

        // ── Delivery capacity guard ──────────────────────────────────
        if (!empty($data['delivery_id'])) {
            $maxActive = (int) Setting::get('max_active_orders', 3);
            [$startOfToday, $endOfToday] = \App\Models\Setting::businessDayRange();
            $activeCount = Order::where('delivery_id', $data['delivery_id'])
                ->where('status', 'received')
                ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
                ->count();

            if ($activeCount >= $maxActive) {
                return response()->json([
                    'errors' => ['delivery_id' => ["عذراً، المندوب لديه الحد الأقصى من الطلبات قيد التوصيل ({$maxActive} طلبات)."]],
                ], 422);
            }
        }

        // ── Delegate to service ──────────────────────────────────────
        $result = $this->orderService->createOrder($data, auth()->id(), adminMode: false);

        // بعد إنشاء الطلب بنجاح
        $callcenter = auth()->user();
        app(\App\Services\CallcenterProfitService::class)
            ->recalculateDayProfits($callcenter, $result['order']);

        return response()->json([
            'success' => true,
            'order_number' => $result['order']->order_number,
            'warning' => $result['addressWarning'],
        ]);
    }

    public function index(Request $request)
    {
        if ($request->header('X-SPA-Navigation')) {
            $shops = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            return response()->json([
                'html' => view('callcenter.orders.partials.index_content', compact('shops'))->render(),
                'title' => 'قائمة الطلبات',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $this->listData($request);
        }
        $shops = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('callcenter.orders.index', compact('shops'));
    }

    public function listData(Request $request)
    {
        $query = Order::with(['client', 'recipientClient', 'delivery', 'items'])
            ->where('callcenter_id', auth()->id())
            ->latest();

        if ($request->filled('status'))
            $query->where('status', $request->status);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', '=', $s)
                    ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', '=', $s)->orWhere('phone2', '=', $s)->orWhere('code', '=', $s));
            });
        }

        $orders = $query->paginate(15);

        return response()->json($orders->through(fn($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'status' => $o->status,
            'client_name' => $o->client?->name ?? '—',
            'client_phone' => $o->client?->phone ?? '—',
            'delivery_name' => $o->delivery?->name ?? '—',
            'shops_count' => $o->items->pluck('shop_id')->unique()->filter()->count(),
            'delivery_fee' => $o->delivery_fee,
            'total' => $o->total,
            'created_at' => $o->created_at->toIso8601String(),
            'sent_to_delivery_at' => $o->sent_to_delivery_at?->toIso8601String(),
            'can_edit' => $o->status === 'pending' && $o->sent_to_delivery_at && now()->lt($o->sent_to_delivery_at),
            'can_send_early' => $o->status === 'pending' && $o->sent_to_delivery_at && now()->lt($o->sent_to_delivery_at),
        ]));
    }

    public function globalSearch(Request $request)
    {
        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('callcenter.orders.partials.global_search_content')->render(),
                'title' => 'بحث الطلبات الشامل',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $s = $request->search;
            if (!$s)
                return response()->json([]);

            $query = Order::with(['client', 'recipientClient', 'delivery', 'callcenter', 'admin', 'items'])->latest();
            $query->where(function ($q) use ($s) {
                $q->where('order_number', '=', $s)
                    ->orWhereHas(
                        'client',
                        fn($c) =>
                        $c->where('phone', '=', $s)->orWhere('phone2', '=', $s)->orWhere('code', '=', $s)
                    );
            });

            return response()->json($query->take(50)->get()->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'client_name' => $o->client?->name ?? '—',
                'client_phone' => $o->client?->phone ?? '—',
                'created_by_name' => $o->callcenter?->name ?? ($o->admin?->name ?? '—'),
                'created_by_role' => $o->callcenter_id ? 'callcenter' : ($o->admin_id ? 'admin' : ''),
                'delivery_name' => $o->delivery?->name ?? '—',
                'shops_count' => $o->items->pluck('shop_id')->unique()->filter()->count(),
                'delivery_fee' => $o->delivery_fee,
                'total' => $o->total,
                'created_at' => $o->created_at->toIso8601String(),
            ]));
        }

        return view('callcenter.orders.global_search');
    }

    public function globalShow($id)
    {
        $order = Order::with(['client.addresses', 'recipientClient.addresses', 'delivery', 'items.shop', 'logs.user'])->findOrFail($id);
        return response()->json(['order' => $this->orderDetail($order)]);
    }

    public function show($id)
    {
        $order = Order::with(['client.addresses', 'recipientClient.addresses', 'delivery', 'items.shop', 'logs.user'])
            ->where('callcenter_id', auth()->id())
            ->findOrFail($id);

        return response()->json(['order' => $this->orderDetail($order)]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::where('callcenter_id', auth()->id())->where('status', 'pending')->findOrFail($id);

        if (now()->gte($order->sent_to_delivery_at)) {
            return response()->json(['success' => false, 'message' => 'انتهت مهلة التعديل — تم إرسال الطلب للمندوب'], 422);
        }

        $items = $request->items ?? [];
        $itemsTotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $deliveryFee = (float) ($request->delivery_fee ?? 0);
        $baseTotal = $itemsTotal + $deliveryFee;

        $discount = (float) ($request->discount ?? 0);
        $discountType = $request->discount_type ?? 'amount';
        $discountAmt = $discountType === 'percent' ? ($baseTotal * $discount / 100) : $discount;

        $maxDiscountPercent = (float) Setting::get('max_discount_percentage', 50);
        if ($baseTotal > 0 && ($discountAmt / $baseTotal * 100) > $maxDiscountPercent) {
            return response()->json([
                'errors' => ['discount' => ["عذراً، نسبة الخصم لا يمكن أن تتجاوز {$maxDiscountPercent}% من إجمالي الطلب. (أقصى قيمة: " . round($baseTotal * $maxDiscountPercent / 100, 2) . " ج)"]],
            ], 422);
        }

        $total = $baseTotal - $discountAmt;
        $isDeliveryChosen = !empty($request->delivery_id);

        if ($isDeliveryChosen && $order->delivery_id != $request->delivery_id) {
            // ── Role/active guard ────────────────────────────────────
            // لا يُسمح بإسناد الطلب لمستخدم ليس بدور توصيل أو غير نشط.
            $deliveryUser = \App\Models\User::where('id', $request->delivery_id)
                ->whereIn('role', ['delivery', 'reserve_delivery'])
                ->where('is_active', true)
                ->first();

            if (!$deliveryUser) {
                return response()->json([
                    'errors' => ['delivery_id' => ['المندوب غير موجود أو غير نشط.']],
                ], 422);
            }

            $maxActive = (int) Setting::get('max_active_orders', 3);
            [$startOfToday, $endOfToday] = \App\Models\Setting::businessDayRange();
            $activeCount = Order::where('delivery_id', $request->delivery_id)
                ->where('status', 'received')
                ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
                ->count();

            if ($activeCount >= $maxActive) {
                return response()->json([
                    'errors' => ['delivery_id' => ["عذراً، المندوب لديه الحد الأقصى من الطلبات قيد التوصيل ({$maxActive} طلبات)."]],
                ], 422);
            }
        }

        // ── Reset hold timer after edit ────────────────────────────
        // بعد التعديل، نُعيد ضبط وقت الإرسال من الصفر حتى لا يُرسل الطلب
        // في منتصف التعديل.
        $holdMinutes = (int) Setting::get('order_hold_minutes', 10);
        $newSentAt = $isDeliveryChosen ? Carbon::now() : Carbon::now()->addMinutes($holdMinutes);

        $order->update([
            'delivery_id' => $request->delivery_id ?: null,
            'is_delivery_chosen' => $isDeliveryChosen,
            'client_address' => $request->client_address,
            'send_to_phone' => $request->send_to_phone ?: null,
            'send_to_phone2' => $request->send_to_phone2 ?: null,
            'send_to_address' => $request->send_to_address ?: null,
            'client_delivery_link' => $request->client_delivery_link ?: null,
            'send_to_delivery_link' => $request->send_to_delivery_link ?: null,
            'notes' => $request->notes,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'discount_type' => $discountType,
            'total' => $total,
            'status' => $isDeliveryChosen ? 'received' : 'pending',
            'accepted_at' => $isDeliveryChosen ? Carbon::now() : null,
            'sent_to_delivery_at' => $newSentAt,
        ]);

        if (!empty($request->client_address) && $order->client) {
            $order->client->addresses()->firstOrCreate(
                ['address' => $request->client_address],
                ['is_default' => $order->client->addresses()->count() === 0]
            );
        }

        if (!empty($request->send_to_phone)) {
            $sendToClient = \App\Models\Client::where('phone', $request->send_to_phone)->first();
            if ($sendToClient) {
                if (!empty($request->send_to_phone2) && $sendToClient->phone2 !== $request->send_to_phone2) {
                    $sendToClient->update(['phone2' => $request->send_to_phone2]);
                }
                if (!empty($request->send_to_name) && $sendToClient->name !== $request->send_to_name) {
                    $sendToClient->update(['name' => $request->send_to_name]);
                }

                if (!empty($request->send_to_address)) {
                    $sendToClient->addresses()->firstOrCreate(
                        ['address' => $request->send_to_address],
                        ['is_default' => $sendToClient->addresses()->count() === 0]
                    );
                }

                $order->update(['send_to_client_id' => $sendToClient->id]);
            } else {
                $sendToClient = \App\Models\Client::create([
                    'phone' => $request->send_to_phone,
                    'phone2' => $request->send_to_phone2,
                    'name' => $request->send_to_name ?? 'Unnamed',
                    'code' => \App\Models\Client::generateCode()
                ]);
                $sendToClient->addresses()->create([
                    'address' => $request->send_to_address ?? '',
                    'is_default' => true,
                ]);
                $order->update(['send_to_client_id' => $sendToClient->id]);
            }
        } else {
            $order->update(['send_to_client_id' => null]);
        }

        $order->items()->delete();

        // Validate shop per item before saving
        foreach ($items as $item) {
            if (!empty($item['item_name']) && empty($item['shop_id'])) {
                return response()->json([
                    'errors' => ['items' => ['يجب اختيار المتجر لكل صنف']],
                ], 422);
            }
        }

        foreach ($items as $item) {
            if (empty($item['item_name']))
                continue;
            OrderItem::create([
                'order_id' => $order->id,
                'shop_id' => $item['shop_id'] ?? null,
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        OrderLog::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'action' => 'تعديل الطلب']);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الطلب',
            'sent_to_delivery_at' => $order->fresh()->sent_to_delivery_at?->toIso8601String(),
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'يجب كتابة سبب الإلغاء',
        ]);

        $order = Order::where('callcenter_id', auth()->id())->where('status', 'pending')->findOrFail($id);

        $order->update(['status' => 'cancelled']);
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'إلغاء الطلب',
            'notes' => $request->reason ?? null,
        ]);

        $notif = \App\Models\AdminNotification::create([
            'type' => 'cancelled',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'message' => "تم إلغاء الطلب #{$order->order_number} بواسطة كول سنتر : " . auth()->user()->name,
            'audience' => 'all', // يظهر للأدمن والكول سنتر
        ]);
        event(new \App\Events\AdminNotificationCreated($notif));

        event(new OrderStatusUpdated(['order_id' => $order->id, 'status' => 'cancelled']));

        ActivityLog::log(
            event: 'order.cancelled',
            description: 'تم إلغاء طلب — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: ['reason' => $request->reason ?? null]
        );

        return response()->json(['success' => true, 'message' => 'تم إلغاء الطلب']);
    }

    /**
     * إيقاف مؤقت مؤقت الإرسال عند فتح نافذة التعديل أو إغلاقها بدون حفظ.
     * يُعيد ضبط sent_to_delivery_at = now() + hold_minutes من جديد.
     */
    public function pauseEdit($id)
    {
        $order = Order::where('callcenter_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        // لا نسمح بإعادة الضبط إذا انتهى الوقت فعلاً قبل الفتح
        // (لو already sent نتجاهل الطلب)
        if (now()->gte($order->sent_to_delivery_at)) {
            return response()->json(['success' => false, 'message' => 'انتهت مهلة التعديل'], 422);
        }

        $holdMinutes = (int) Setting::get('order_hold_minutes', 10);
        $newSentAt = now()->addMinutes($holdMinutes);

        $order->update(['sent_to_delivery_at' => $newSentAt]);

        return response()->json([
            'success' => true,
            'sent_to_delivery_at' => $newSentAt->toIso8601String(),
        ]);
    }

    public function sendEarly($id)
    {
        $order = Order::where('callcenter_id', auth()->id())->where('status', 'pending')->findOrFail($id);
        $order->update(['sent_to_delivery_at' => now()]);
        OrderLog::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'action' => 'إرسال مبكر للمندوب']);

        ActivityLog::log(
            event: 'order.sent_early',
            description: 'إرسال مبكر — ' . $order->order_number,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number
        );

        try {
            event(new OrderStatusUpdated(['order_id' => $order->id, 'status' => 'pending', 'early' => true]));
        } catch (\Throwable) {
        }

        // ✅ dispatch فوري — الـ Job المجدول القديم سيتجاهله الـ worker لو وصله متأخراً
        // لأن الـ Job بيتحقق من status = pending قبل الإرسال
        \App\Jobs\NotifyPrimaryDeliveryOrder::dispatch($order->id);
        \App\Jobs\NotifyReserveDeliveryOrder::dispatch($order->id)
            ->delay(now()->addMinutes((int) \App\Models\Setting::get('reserve_delay_minutes', 5)));

        return response()->json(['success' => true, 'message' => 'تم إرسال الطلب للمندوب الآن']);
    }

    public function downloadPdf($id)
    {
        $order = Order::with(['client', 'recipientClient', 'callcenter', 'admin', 'delivery', 'items.shop', 'logs.user'])->findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pdf.order_single', compact('order'))->setPaper('a4', 'portrait');
        return $pdf->download($order->order_number . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /** Consistent order detail shape for show() / globalShow(). */
    private function orderDetail(\App\Models\Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'notes' => $order->notes,
            'client_address' => $order->client_address,
            'send_to_phone' => $order->send_to_phone,
            'send_to_phone2' => $order->send_to_phone2,
            'send_to_name' => $order->recipientClient?->name,
            'send_to_address' => $order->send_to_address,
            'delivery_fee' => $order->delivery_fee,
            'discount' => $order->discount,
            'discount_type' => $order->discount_type,
            'total' => $order->total,
            'created_at' => $order->created_at->toIso8601String(),
            'sent_to_delivery_at' => $order->sent_to_delivery_at?->toIso8601String(),
            'accepted_at' => $order->accepted_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'client' => $order->client
                ? [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                    'phone' => $order->client->phone,
                    'phone2' => $order->client->phone2,
                    'code' => $order->client->code,
                    'addresses' => $order->client->addresses->map(fn($a) => ['id' => $a->id, 'address' => $a->address])
                ]
                : null,
            'recipient_client' => $order->recipientClient
                ? [
                    'id' => $order->recipientClient->id,
                    'name' => $order->recipientClient->name,
                    'phone' => $order->recipientClient->phone,
                    'phone2' => $order->recipientClient->phone2,
                    'code' => $order->recipientClient->code,
                    'addresses' => $order->recipientClient->addresses->map(fn($a) => ['id' => $a->id, 'address' => $a->address])
                ]
                : null,
            'delivery' => $order->delivery
                ? ['id' => $order->delivery->id, 'name' => $order->delivery->name]
                : null,
            'items' => $order->items->map(fn($i) => [
                'item_name' => $i->item_name,
                'shop_id' => $i->shop_id,
                'shop' => $i->shop?->name ?? '—',
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'total' => $i->total,
            ]),
            'logs' => $order->logs->map(fn($l) => [
                'user' => $l->user?->name ?? 'النظام',
                'action' => $l->action,
                'notes' => $l->notes,
                'created_at' => $l->created_at->toIso8601String(),
            ]),
        ];
    }
}