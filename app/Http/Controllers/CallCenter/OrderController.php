<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function create()
    {
        $shops = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('callcenter.orders.partials.create_content', compact('shops'))->render(),
                'title'      => 'إنشاء طلب',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('callcenter.orders.create', compact('shops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'client_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'code.required' => 'الكود مطلوب',
            'name.required' => 'اسم العميل مطلوب',
            'client_address.required' => 'العنوان مطلوب',
            'items.required' => 'يجب إضافة صنف واحد على الأقل',
        ]);

        // 1. Find or create client
        $client = Client::where('phone', $request->phone)->first();
        $addressWarning = null;

        if ($client) {
            // Update fields if changed
            $update = [];
            if ($request->name && $client->name !== $request->name)
                $update['name'] = $request->name;
            if ($request->phone2 && $client->phone2 !== $request->phone2)
                $update['phone2'] = $request->phone2;
            if ($update)
                $client->update($update);
        } else {
            // Create new client
            $client = Client::create([
                'phone' => $request->phone,
                'phone2' => $request->phone2,
                'name' => $request->name,
                'code' => $request->code,
            ]);

            ActivityLog::log(
                event: 'client.created_inline',
                description: 'تم إضافة عميل جديد أثناء الفاتورة (كول سنتر) — ' . $client->name,
                subjectType: 'client',
                subjectId: $client->id,
                subjectLabel: $client->name,
                properties: ['client_code' => $client->code, 'phone' => $client->phone]
            );
        }

        // 2. Handle new address
        if ($request->is_new_address && $request->client_address) {
            $addrCount = $client->addresses()->count();
            if ($addrCount < 5) {
                ClientAddress::create([
                    'client_id' => $client->id,
                    'address' => $request->client_address,
                    'is_default' => $addrCount === 0,
                ]);
            } else {
                $addressWarning = 'تم حفظ الطلب لكن لم يُضَف العنوان — العميل لديه 5 عناوين بالفعل';
            }
        }

        // 3. Calculate total
        $holdMinutes = (int) Setting::get('order_hold_minutes', 10);
        $items = $request->items;
        $itemsTotal = collect($items)->sum(fn($i) => ($i['quantity'] * $i['unit_price']));
        $discount = (float) ($request->discount ?? 0);
        $discountType = $request->discount_type ?? 'amount';
        $discountAmt = $discountType === 'percent' ? ($itemsTotal * $discount / 100) : $discount;
        
        // 3.1 Validate max discount
        $maxDiscountPercent = (float) Setting::get('max_discount_percentage', 50);
        if ($itemsTotal > 0 && ($discountAmt / $itemsTotal * 100) > $maxDiscountPercent) {
            return response()->json([
                'errors' => ['discount' => ["عذراً، نسبة الخصم لا يمكن أن تتجاوز {$maxDiscountPercent}% من إجمالي الأصناف. (أقصى قيمة: " . round($itemsTotal * $maxDiscountPercent / 100, 2) . " ج)"]]
            ], 422);
        }

        $deliveryFee = (float) ($request->delivery_fee ?? 0);
        $total = $itemsTotal + $deliveryFee - $discountAmt;

        // 4. Create order
        $isDeliveryChosen = !empty($request->delivery_id);
        
        if ($isDeliveryChosen) {
            $maxActive = (int) Setting::get('max_active_orders', 3);
            list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
            $activeCount = Order::where('delivery_id', $request->delivery_id)
                ->where('status', 'received')
                ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
                ->count();

            if ($activeCount >= $maxActive) {
                return response()->json([
                    'errors' => ['delivery_id' => ["عذراً، المندوب لديه الحد الأقصى من الطلبات قيد التوصيل ({$maxActive} طلبات)."]]
                ], 422);
            }
        }

        $orderStatus = $isDeliveryChosen ? 'received' : 'pending';
        $acceptedAt = $isDeliveryChosen ? Carbon::now() : null;
        $sentToDeliveryAt = $isDeliveryChosen ? Carbon::now() : Carbon::now()->addMinutes($holdMinutes);

        $order = Order::create([
            'order_number' => Order::generateNumber(),
            'callcenter_id' => auth()->id(),
            'delivery_id' => $request->delivery_id ?: null,
            'is_delivery_chosen' => $isDeliveryChosen,
            'client_id' => $client->id,
            'client_address' => $request->client_address,
            'send_to_phone' => $request->send_to_phone ?: null,
            'send_to_address' => $request->send_to_address ?: null,
            'notes' => $request->notes,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'discount_type' => $discountType,
            'total' => $total,
            'status' => $orderStatus,
            'accepted_at' => $acceptedAt,
            'sent_to_delivery_at' => $sentToDeliveryAt,
        ]);

        // Handle send-to customer creation
        if ($request->filled('send_to_phone') && !$request->filled('send_to_client_id')) {
            $sendToClient = \App\Models\Client::firstOrCreate(
                ['phone' => $request->send_to_phone],
                [
                    'name'  => $request->send_to_name ?: 'Unnamed',
                    'code'  => $request->send_to_code ?: \App\Models\Client::generateCode(),
                    'phone2'=> null,
                ]
            );
            if ($sendToClient->wasRecentlyCreated) {
                $sendToClient->addresses()->create([
                    'address'    => $request->send_to_address ?? '',
                    'is_default' => true,
                ]);
                \App\Models\ActivityLog::log(
                    event: 'client.created_sendto',
                    description: 'تم إضافة عميل الإرسال إليه تلقائياً — ' . $sendToClient->name,
                    subjectType: 'client', subjectId: $sendToClient->id,
                    subjectLabel: $sendToClient->name,
                    properties: ['phone' => $sendToClient->phone, 'code' => $sendToClient->code]
                );
            }
        }

        // 5. Create items
        foreach ($items as $item) {
            if (empty($item['item_name']))
                continue;
            OrderItem::create([
                'order_id' => $order->id,
                'shop_id' => $item['shop_id'] ?: null,
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        // 6. Log
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'تم إنشاء الطلب',
            'notes' => 'بواسطة كول سنتر: ' . auth()->user()->name,
        ]);

        ActivityLog::log(
            event: 'order.created_cc',
            description: 'تم إنشاء طلب جديد من كول سنتر',
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: [
                'order_number' => $order->order_number,
                'client_name'  => $client->name,
                'client_code'  => $client->code,
                'total'        => $order->total,
            ]
        );

        // 7. Fire event
        try {
            event(new OrderStatusUpdated(['order_id' => $order->id, 'status' => $orderStatus, 'order_number' => $order->order_number, 'delivery_id' => $order->delivery_id]));
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'warning' => $addressWarning,
        ]);
    }

    public function index(Request $request)
    {
        if ($request->header('X-SPA-Navigation')) {
            $shops = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            return response()->json([
                'html'       => view('callcenter.orders.partials.index_content', compact('shops'))->render(),
                'title'      => 'قائمة الطلبات',
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
        $query = Order::with(['client', 'delivery', 'items'])
            ->where('callcenter_id', auth()->id())
            ->latest();


        if ($request->filled('status'))
            $query->where('status', $request->status);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%$s%")
                    ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
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
                'html'       => view('callcenter.orders.partials.global_search_content')->render(),
                'title'      => 'بحث الطلبات الشامل',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $s = $request->search;
            if (!$s) return response()->json([]);

            $query = Order::with(['client', 'delivery', 'callcenter'])
                ->latest();

            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%$s%")
                    ->orWhereHas('client', fn($c) => 
                        $c->where('code', 'like', "%$s%")
                          ->orWhere('phone', 'like', "%$s%")
                    );
            });

            $orders = $query->take(50)->get();

            return response()->json($orders->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'client_name' => $o->client?->name ?? '—',
                'client_phone' => $o->client?->phone ?? '—',
                'delivery_name' => $o->delivery?->name ?? '—',
                'callcenter_name' => $o->callcenter?->name ?? '—',
                'total' => $o->total,
                'created_at' => $o->created_at->toIso8601String(),
            ]));
        }

        return view('callcenter.orders.global_search');
    }

    public function globalShow($id)
    {
        $order = Order::with(['client', 'delivery', 'items.shop', 'logs.user'])
            ->findOrFail($id);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'notes' => $order->notes,
                'client_address' => $order->client_address,
                'send_to_phone' => $order->send_to_phone,
                'send_to_address' => $order->send_to_address,
                'delivery_fee' => $order->delivery_fee,
                'discount' => $order->discount,
                'discount_type' => $order->discount_type,
                'total' => $order->total,
                'created_at' => $order->created_at->toIso8601String(),
                'sent_to_delivery_at' => $order->sent_to_delivery_at?->toIso8601String(),
                'accepted_at' => $order->accepted_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'client' => $order->client ? ['name' => $order->client->name, 'phone' => $order->client->phone, 'code' => $order->client->code] : null,
                'delivery' => $order->delivery ? ['id' => $order->delivery->id, 'name' => $order->delivery->name] : null,
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
                    'created_at' => $l->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function show($id)
    {
        $order = Order::with(['client', 'delivery', 'items.shop', 'logs.user'])
            ->where('callcenter_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'notes' => $order->notes,
                'client_address' => $order->client_address,
                'send_to_phone' => $order->send_to_phone,
                'send_to_address' => $order->send_to_address,
                'delivery_fee' => $order->delivery_fee,
                'discount' => $order->discount,
                'discount_type' => $order->discount_type,
                'total' => $order->total,
                'created_at' => $order->created_at->toIso8601String(),
                'sent_to_delivery_at' => $order->sent_to_delivery_at?->toIso8601String(),
                'accepted_at' => $order->accepted_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'client' => $order->client ? ['name' => $order->client->name, 'phone' => $order->client->phone, 'code' => $order->client->code] : null,
                'delivery' => $order->delivery ? ['id' => $order->delivery->id, 'name' => $order->delivery->name] : null,
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
                    'created_at' => $l->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::where('callcenter_id', auth()->id())->where('status', 'pending')->findOrFail($id);

        if (now()->gte($order->sent_to_delivery_at)) {
            return response()->json(['success' => false, 'message' => 'انتهت مهلة التعديل — تم إرسال الطلب للدلفري'], 422);
        }

        // Same store logic but update
        $items = $request->items ?? [];
        $itemsTotal = collect($items)->sum(fn($i) => ($i['quantity'] * $i['unit_price']));
        $discount = (float) ($request->discount ?? 0);
        $discountType = $request->discount_type ?? 'amount';
        $discountAmt = $discountType === 'percent' ? ($itemsTotal * $discount / 100) : $discount;

        // Validate max discount
        $maxDiscountPercent = (float) Setting::get('max_discount_percentage', 50);
        if ($itemsTotal > 0 && ($discountAmt / $itemsTotal * 100) > $maxDiscountPercent) {
            return response()->json([
                'errors' => ['discount' => ["عذراً، نسبة الخصم لا يمكن أن تتجاوز {$maxDiscountPercent}% من إجمالي الأصناف. (أقصى قيمة: " . round($itemsTotal * $maxDiscountPercent / 100, 2) . " ج)"]]
            ], 422);
        }

        $deliveryFee = (float) ($request->delivery_fee ?? 0);
        $total = $itemsTotal + $deliveryFee - $discountAmt;

        $isDeliveryChosen = !empty($request->delivery_id);
        
        if ($isDeliveryChosen && $order->delivery_id != $request->delivery_id) {
            $maxActive = (int) Setting::get('max_active_orders', 3);
            list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
            $activeCount = Order::where('delivery_id', $request->delivery_id)
                ->where('status', 'received')
                ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
                ->count();

            if ($activeCount >= $maxActive) {
                return response()->json([
                    'errors' => ['delivery_id' => ["عذراً، المندوب لديه الحد الأقصى من الطلبات قيد التوصيل ({$maxActive} طلبات)."]]
                ], 422);
            }
        }

        $orderStatus = $isDeliveryChosen ? 'received' : 'pending';
        $acceptedAt = $isDeliveryChosen ? Carbon::now() : null;

        $order->update([
            'delivery_id' => $request->delivery_id ?: null,
            'is_delivery_chosen' => $isDeliveryChosen,
            'client_address' => $request->client_address,
            'send_to_phone' => $request->send_to_phone ?: null,
            'send_to_address' => $request->send_to_address ?: null,
            'notes' => $request->notes,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'discount_type' => $discountType,
            'total' => $total,
            'status' => $orderStatus,
            'accepted_at' => $acceptedAt,
        ]);

        $order->items()->delete();
        foreach ($items as $item) {
            if (empty($item['item_name']))
                continue;
            OrderItem::create([
                'order_id' => $order->id,
                'shop_id' => $item['shop_id'] ?: null,
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        OrderLog::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'action' => 'تعديل الطلب']);

        return response()->json(['success' => true, 'message' => 'تم تعديل الطلب']);
    }

    public function cancel(Request $request, $id)
    {
        $order = Order::where('callcenter_id', auth()->id())->where('status', 'pending')->findOrFail($id);

        $order->update(['status' => 'cancelled']);
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'إلغاء الطلب',
            'notes' => $request->reason ?? null,
        ]);

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

    public function sendEarly($id)
    {
        $order = Order::where('callcenter_id', auth()->id())->where('status', 'pending')->findOrFail($id);
        $order->update(['sent_to_delivery_at' => now()]);
        OrderLog::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'action' => 'إرسال مبكر للدلفري']);

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

        return response()->json(['success' => true, 'message' => 'تم إرسال الطلب للدلفري الآن']);
    }

    public function downloadPdf($id)
    {
        $order = Order::with(['client', 'callcenter', 'delivery', 'items.shop', 'logs.user'])
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pdf.order_single', compact('order'))->setPaper('a4', 'portrait');

        return $pdf->download($order->order_number . '.pdf');
    }
}
