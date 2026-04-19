<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Shop;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Events\OrderStatusUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    // ─── Helpers ───────────────────────────────────────────────────

    /** Active deliveries for the order-create dropdown. */
    private function activeDeliveries(): array
    {
        return User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->whereHas('shifts', fn($q) => $q->where('is_active', true)->where('date', today()->toDateString()))
            ->withCount(['deliveryOrders as orders_today' => fn($q) => $q->where('created_at', '>=', today()->startOfDay())->whereIn('status', ['received', 'delivered'])])
            ->with(['shifts' => fn($q) => $q->where('is_active', true)->where('date', today()->toDateString())])
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'role'       => $d->role,
                'orders_today' => $d->orders_today,
                'max_orders' => $d->shifts->first()?->max_orders ?? 10,
            ])
            ->toArray();
    }

    // ─── Create Order ─────────────────────────────────────────────

    public function create()
    {
        $shops       = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $deliveries  = $this->activeDeliveries();

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.orders.partials.create_content', compact('shops', 'deliveries'))->render(),
                'title'      => 'إنشاء طلب (أدمن)',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.orders.create', compact('shops', 'deliveries'));
    }

    /**
     * Store a new admin order.
     *
     * Differences from CC store:
     *  - callcenter_id = null  (marks as admin order)
     *  - status = 'received'   (sent directly to delivery)
     *  - sent_to_delivery_at = now()  (no hold period)
     *  - delivery_id is REQUIRED
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone'             => 'required|string',
            'code'              => 'required|string',
            'name'              => 'required|string',
            'client_address'    => 'required|string',
            'delivery_id'       => 'required|exists:users,id',
            'items'             => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit_price'=> 'required|numeric|min:0',
        ], [
            'phone.required'      => 'رقم الهاتف مطلوب',
            'code.required'       => 'الكود مطلوب',
            'name.required'       => 'اسم العميل مطلوب',
            'client_address.required' => 'العنوان مطلوب',
            'delivery_id.required'=> 'يجب تحديد المندوب',
            'delivery_id.exists'  => 'المندوب غير موجود',
            'items.required'      => 'يجب إضافة صنف واحد على الأقل',
        ]);

        // 1. Find or create client
        $client = Client::where('phone', $request->phone)->first();
        $addressWarning = null;

        if ($client) {
            $update = [];
            if ($request->name && $client->name !== $request->name) $update['name'] = $request->name;
            if ($request->phone2 && $client->phone2 !== $request->phone2) $update['phone2'] = $request->phone2;
            if ($update) $client->update($update);
        } else {
            $client = Client::create([
                'phone'  => $request->phone,
                'phone2' => $request->phone2,
                'name'   => $request->name,
                'code'   => $request->code,
            ]);

            ActivityLog::log(
                event: 'client.created_inline',
                description: 'تم إضافة عميل جديد أثناء الفاتورة (أدمن) — ' . $client->name,
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
                    'client_id'  => $client->id,
                    'address'    => $request->client_address,
                    'is_default' => $addrCount === 0,
                ]);
            } else {
                $addressWarning = 'تم حفظ الطلب لكن لم يُضَف العنوان — العميل لديه 5 عناوين بالفعل';
            }
        }

        // 3. Compute totals
        $items       = $request->items;
        $itemsTotal  = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $discount    = (float) ($request->discount ?? 0);
        $discountType= $request->discount_type ?? 'amount';
        $discountAmt = $discountType === 'percent' ? ($itemsTotal * $discount / 100) : $discount;

        $maxDiscountPercent = (float) \App\Models\Setting::get('max_discount_percentage', 50);
        if ($itemsTotal > 0 && ($discountAmt / $itemsTotal * 100) > $maxDiscountPercent) {
            return response()->json([
                'errors' => ['discount' => ["عذراً، نسبة الخصم لا يمكن أن تتجاوز {$maxDiscountPercent}% من إجمالي الأصناف. (أقصى قيمة: " . round($itemsTotal * $maxDiscountPercent / 100, 2) . " ج)"]]
            ], 422);
        }

        $deliveryFee = (float) ($request->delivery_fee ?? 0);
        $total       = $itemsTotal + $deliveryFee - $discountAmt;

        // 4. Create order — sent directly to delivery
        $order = Order::create([
            'order_number'       => Order::generateNumber(),
            'callcenter_id'      => null,       // ← admin order marker
            'delivery_id'        => $request->delivery_id,
            'is_delivery_chosen' => true,
            'client_id'          => $client->id,
            'client_address'     => $request->client_address,
            'send_to_phone'      => $request->send_to_phone ?: null,
            'send_to_address'    => $request->send_to_address ?: null,
            'notes'              => $request->notes,
            'delivery_fee'       => $deliveryFee,
            'discount'           => $discount,
            'discount_type'      => $discountType,
            'total'              => $total,
            'status'             => 'received',  // ← direct to delivery
            'sent_to_delivery_at'=> now(),
            'accepted_at'        => now(),
        ]);

        // 5. Create items
        foreach ($items as $item) {
            if (empty($item['item_name'])) continue;
            OrderItem::create([
                'order_id'   => $order->id,
                'shop_id'    => $item['shop_id'] ?: null,
                'item_name'  => $item['item_name'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total'      => $item['quantity'] * $item['unit_price'],
            ]);
        }

        // 6. Log
        OrderLog::create([
            'order_id' => $order->id,
            'user_id'  => auth()->id(),
            'action'   => 'إنشاء طلب مباشر من الأدمن',
            'notes'    => 'بواسطة: ' . auth()->user()->name,
        ]);

        ActivityLog::log(
            event: 'order.created_admin',
            description: 'تم إنشاء طلب جديد من الأدمن',
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: [
                'order_number' => $order->order_number,
                'client_name'  => $client->name,
                'client_code'  => $client->code,
                'total'        => $order->total,
                'delivery_id'  => $order->delivery_id,
            ]
        );

        // 7. Broadcast to delivery agent
        try {
            event(new OrderStatusUpdated([
                'order_id'     => $order->id,
                'status'       => 'received',
                'order_number' => $order->order_number,
                'delivery_id'  => $order->delivery_id,
            ]));
        } catch (\Throwable) {}

        return response()->json([
            'success'      => true,
            'order_number' => $order->order_number,
            'warning'      => $addressWarning,
        ]);
    }

    // ─── Client Search (reuse CC logic) ──────────────────────────

    public function searchClient(Request $request)
    {
        $phone = $request->phone;
        $code  = $request->code;
        if (!$phone && !$code) return response()->json(['found' => false]);

        $query = Client::with('addresses');
        if ($phone) $query->where('phone', $phone);
        else        $query->where('code', $code);

        $client = $query->first();
        if (!$client) return response()->json(['found' => false]);

        return response()->json([
            'found'     => true,
            'id'        => $client->id,
            'name'      => $client->name,
            'code'      => $client->code,
            'phone'     => $client->phone,
            'phone2'    => $client->phone2,
            'addresses' => $client->addresses->map(fn($a) => [
                'id'         => $a->id,
                'address'    => $a->address,
                'is_default' => $a->is_default,
            ]),
        ]);
    }

    // ─── Delivery Settlement (Admin → Treasury) ───────────────────

    /**
     * GET /admin/delivery/{id}/settlement
     *
     * Returns unsettled admin orders (callcenter_id IS NULL, status=delivered,
     * is_settled=false) for a specific delivery agent.
     * Settlement amount = total (items + delivery_fee - discount).
     */
    public function deliverySettlement($id)
    {
        $delivery = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);

        $orders = Order::where('delivery_id', $id)
            ->whereNull('callcenter_id')      // admin orders only
            ->where('status', 'delivered')
            ->where('is_settled', false)
            ->with(['client', 'items'])
            ->latest('delivered_at')
            ->get();

        $summary = [
            'count'           => $orders->count(),
            'total_amount'    => $orders->sum('total'),         // items + fee - discount
            'total_items_val' => $orders->sum(fn($o) => $o->items->sum('total')),
            'total_fees'      => $orders->sum('delivery_fee'),
            'total_discounts' => $orders->sum('discount'),
        ];

        $mapped = $orders->map(fn($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'client'       => $o->client?->name ?? '—',
            'total'        => $o->total,
            'delivery_fee' => $o->delivery_fee,
            'discount'     => $o->discount,
            'items_count'  => $o->items->count(),
            'delivered_at' => $o->delivered_at?->toIso8601String(),
        ]);

        return response()->json([
            'delivery' => ['id' => $delivery->id, 'name' => $delivery->name],
            'summary'  => $summary,
            'orders'   => $mapped,
        ]);
    }

    /**
     * POST /admin/delivery/{id}/settlement
     *
     * Marks all unsettled admin orders as settled and records the total
     * in the treasury as a 'settlement' transaction.
     */
    public function doDeliverySettlement($id)
    {
        $delivery = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);

        DB::transaction(function () use ($delivery) {
            $orders = Order::where('delivery_id', $delivery->id)
                ->whereNull('callcenter_id')
                ->where('status', 'delivered')
                ->where('is_settled', false)
                ->lockForUpdate()
                ->get();

            if ($orders->isEmpty()) {
                abort(422, 'لا توجد طلبات مستحقة للتسوية');
            }

            $totalAmount = $orders->sum('total');

            // Mark orders as settled
            Order::whereIn('id', $orders->pluck('id'))
                ->update(['is_settled' => true]);

            // Record in Treasury
            TreasuryTransaction::create([
                'type'             => 'settlement',
                'source_type'      => 'admin_delivery_settlement',
                'source_id'        => null,
                'amount'           => $totalAmount,
                'by_whom'          => $delivery->name,
                'note'             => "تسوية مندوب: {$delivery->name} — " . $orders->count() . " طلب",
                'recorded_by'      => auth()->id(),
                'transaction_date' => now()->toDateString(),
            ]);

            ActivityLog::log(
                event: 'settlement.admin_delivery',
                description: "تسوية مندوب مع الأدمن — {$delivery->name}",
                subjectType: 'settlement',
                subjectId: $delivery->id,
                subjectLabel: $delivery->name,
                properties: [
                    'delivery_name'  => $delivery->name,
                    'orders_count'   => $orders->count(),
                    'total_amount'   => $totalAmount,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => "تمت التسوية بنجاح وتم تسجيلها في الخزنة",
        ]);
    }
}
