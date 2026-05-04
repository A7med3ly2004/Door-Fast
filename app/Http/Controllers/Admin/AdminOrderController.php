<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;

/**
 * AdminOrderController
 *
 * المسؤوليات:
 *  1. Validation — هنا فقط.
 *  2. استدعاء OrderService بالـ validated data (adminMode: true).
 *  3. بناء الـ Response.
 *
 * يشترك مع CC\OrderController في نفس OrderService::createOrder().
 */
class AdminOrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    // ─── Helpers ───────────────────────────────────────────────────

    /** Active deliveries for the order-create dropdown. */
    private function activeDeliveries(): array
    {
        [$startOfToday, $endOfToday] = Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        return User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->whereHas('shifts', fn($q) => $q->where('is_active', true)->where('date', $businessDate))
            ->withCount(['deliveryOrders as orders_today' => fn($q) => $q
                ->whereBetween('created_at', [$startOfToday, $endOfToday])
                ->whereIn('status', ['received', 'delivered'])])
            ->with(['shifts' => fn($q) => $q->where('is_active', true)->where('date', $businessDate)])
            ->get()
            ->map(fn($d) => [
                'id'          => $d->id,
                'name'        => $d->name,
                'role'        => $d->role,
                'orders_today'=> $d->orders_today,
                'max_orders'  => $d->shifts->first()?->max_orders ?? 10,
            ])
            ->toArray();
    }

    // ─── Create Order ─────────────────────────────────────────────

    public function create()
    {
        $shops      = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $deliveries = $this->activeDeliveries();

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
     * Differences from CC store handled transparently by OrderService (adminMode: true):
     *  - callcenter_id = null
     *  - sent_to_delivery_at = now() always (no hold period)
     */
    public function store(Request $request)
    {
        // ── Validation (Controller responsibility) ──────────────────
        $validated = $request->validate([
            'phone'              => 'required|string',
            'code'               => 'required|string',
            'name'               => 'required|string',
            'client_address'     => 'required|string',
            'delivery_id'        => 'nullable|exists:users,id',
            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.shop_id'    => 'nullable|exists:shops,id',
        ], [
            'phone.required'          => 'رقم الهاتف مطلوب',
            'code.required'           => 'الكود مطلوب',
            'name.required'           => 'اسم العميل مطلوب',
            'client_address.required' => 'العنوان مطلوب',
            'delivery_id.exists'      => 'المندوب غير موجود',
            'items.required'          => 'يجب إضافة صنف واحد على الأقل',
        ]);

        // Merge remaining fields the service needs
        $data = array_merge($validated, $request->only([
            'phone2', 'is_new_address',
            'discount', 'discount_type', 'delivery_fee',
            'notes', 'send_to_phone', 'send_to_address',
            'send_to_name', 'send_to_code', 'send_to_client_id',
        ]));

        // ── Discount guard (HTTP-layer) ──────────────────────────────
        $items       = $data['items'];
        $itemsTotal  = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $discount    = (float) ($data['discount'] ?? 0);
        $discountType= $data['discount_type'] ?? 'amount';
        $discountAmt = $discountType === 'percent' ? ($itemsTotal * $discount / 100) : $discount;
        $maxDiscountPercent = (float) Setting::get('max_discount_percentage', 50);

        if ($itemsTotal > 0 && ($discountAmt / $itemsTotal * 100) > $maxDiscountPercent) {
            return response()->json([
                'errors' => ['discount' => ["عذراً، نسبة الخصم لا يمكن أن تتجاوز {$maxDiscountPercent}% من إجمالي الأصناف. (أقصى قيمة: " . round($itemsTotal * $maxDiscountPercent / 100, 2) . " ج)"]],
            ], 422);
        }

        // ── Delivery capacity guard ──────────────────────────────────
        $deliveryId = $request->filled('delivery_id') ? $request->delivery_id : null;
        if ($deliveryId) {
            $maxActive = (int) Setting::get('max_active_orders', 3);
            [$startOfToday, $endOfToday] = Setting::businessDayRange();
            $activeCount = Order::where('delivery_id', $deliveryId)
                ->where('status', 'received')
                ->whereBetween('accepted_at', [$startOfToday, $endOfToday])
                ->count();

            if ($activeCount >= $maxActive) {
                return response()->json([
                    'errors' => ['delivery_id' => ["عذراً، المندوب لديه الحد الأقصى من الطلبات قيد التوصيل ({$maxActive} طلبات)."]],
                ], 422);
            }
        }

        // ── Delegate to service (adminMode: true = callcenter_id null, no hold) ─
        $result = $this->orderService->createOrder($data, callcenterId: null, adminMode: true);

        $successMsg = $deliveryId
            ? 'تم إرسال الطلب مباشرة للمندوب المحدد'
            : 'تم إرسال الطلب إلى قائمة الطلبات الجديدة';

        return response()->json([
            'success'      => true,
            'order_number' => $result['order']->order_number,
            'message'      => $successMsg,
            'has_delivery' => (bool) $deliveryId,
            'warning'      => $result['addressWarning'],
        ]);
    }

    // ─── Client Search ────────────────────────────────────────────

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
}
