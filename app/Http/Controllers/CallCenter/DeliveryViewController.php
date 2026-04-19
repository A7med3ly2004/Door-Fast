<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryViewController extends Controller
{
    public function index()
    {
        $maxOrders  = (int) Setting::get('max_orders_per_delivery', 10);

        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->with(['shifts' => fn($q) => $q->where('date', today()->toDateString())])
            ->withCount(['deliveryOrders as orders_today'    => fn($q) => $q->where('created_at', '>=', today()->startOfDay())])
            ->withCount(['deliveryOrders as cancelled_today' => fn($q) => $q->where('created_at', '>=', today()->startOfDay())->where('status', 'cancelled')])
            ->get()
            ->map(fn($d) => [
                'id'               => $d->id,
                'name'             => $d->name,
                'role'             => $d->role,
                'phone'            => $d->phone,
                'is_on_shift'      => $d->shifts->where('is_active', true)->isNotEmpty(),
                'cc_shift_enabled' => (bool) $d->cc_shift_enabled,
                'orders_today'     => $d->orders_today,
                'cancelled_today'  => $d->cancelled_today,
                'max_orders'       => $d->shifts->first()?->max_orders ?? $maxOrders,
                'revenue_today'    => Order::where('delivery_id', $d->id)
                                          ->where('callcenter_id', auth()->id())
                                          ->where('status', 'delivered')
                                          ->where('is_settled', false)
                                          ->sum('total'),
            ]);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('callcenter.delivery.partials.content', compact('deliveries'))->render(),
                'title'      => 'إدارة المناديب',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('callcenter.delivery.index', compact('deliveries'));
    }

    public function active()
    {
        $maxOrders = (int) Setting::get('max_orders_per_delivery', 10);

        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->whereHas('shifts', fn($q) => $q->where('is_active', true)->where('date', today()->toDateString()))
            ->with(['shifts' => fn($q) => $q->where('is_active', true)->where('date', today()->toDateString())])
            ->withCount(['deliveryOrders as orders_today' => fn($q) => $q->where('created_at', '>=', today()->startOfDay())->whereIn('status', ['received', 'delivered'])])
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'role'       => $d->role,
                'orders_today' => $d->orders_today,
                'max_orders' => $d->shifts->first()?->max_orders ?? $maxOrders,
            ]);

        return response()->json($deliveries);
    }

    /**
     * Returns ALL delivery users (active accounts) with their shift
     * status for the CC delivery management table.
     * Used by reloadDeliveries() in the CC partial JS.
     */
    public function allForCC()
    {
        $maxOrders = (int) Setting::get('max_orders_per_delivery', 10);

        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->with(['shifts' => fn($q) => $q->where('date', today()->toDateString())])
            ->withCount(['deliveryOrders as orders_today' => fn($q) => $q->where('created_at', '>=', today()->startOfDay())])
            ->withCount(['deliveryOrders as cancelled_today' => fn($q) => $q->where('created_at', '>=', today()->startOfDay())->where('status', 'cancelled')])
            ->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'role'            => $d->role,
                'phone'           => $d->phone,
                'is_on_shift'     => $d->shifts->where('is_active', true)->isNotEmpty(),
                'cc_shift_enabled'=> (bool) $d->cc_shift_enabled,
                'orders_today'    => $d->orders_today,
                'cancelled_today' => $d->cancelled_today,
                'max_orders'      => $d->shifts->first()?->max_orders ?? $maxOrders,
                'revenue_today'   => Order::where('delivery_id', $d->id)
                                         ->where('callcenter_id', auth()->id())
                                         ->where('status', 'delivered')
                                         ->where('is_settled', false)
                                         ->sum('total'),
            ]);

        return response()->json($deliveries);
    }


    public function toggleShift(Request $request, $id)
    {
        $delivery  = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);
        $today     = today();
        $maxOrders = (int) Setting::get('max_orders_per_delivery', 10);

        $shift = Shift::where('delivery_id', $id)
                      ->where('date', $today->toDateString())
                      ->first();

        if ($shift && $shift->is_active) {
            // ── Turn OFF ──────────────────────────────────────────
            $shift->update(['is_active' => false, 'ended_at' => now()]);
            // Revoke CC permission so the delivery agent cannot re-start on their own
            $delivery->update(['cc_shift_enabled' => false]);

            $message = 'تم إنهاء وردية ' . $delivery->name;
            $is_on   = false;
        } else {
            // ── Turn ON ───────────────────────────────────────────
            // Grant CC permission flag FIRST
            $delivery->update(['cc_shift_enabled' => true]);

            if ($shift) {
                $shift->update(['is_active' => true, 'started_at' => now(), 'ended_at' => null]);
            } else {
                Shift::create([
                    'delivery_id' => $id,
                    'date'        => $today,
                    'started_at'  => now(),
                    'is_active'   => true,
                    'max_orders'  => $maxOrders,
                ]);
            }

            $message = 'تم بدء وردية ' . $delivery->name;
            $is_on   = true;
        }

        ActivityLog::log(
            event: $is_on ? 'shift.cc_started' : 'shift.cc_ended',
            description: $message,
            subjectType: 'shift',
            subjectId: $delivery->id,
            subjectLabel: $delivery->name
        );

        return response()->json(['success' => true, 'message' => $message, 'is_on' => $is_on]);
    }

    public function settlement($id)
    {
        $delivery = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);

        $orders = Order::with(['client', 'items'])
            ->where('delivery_id', $id)
            ->where('callcenter_id', auth()->id())
            ->where('status', 'delivered')
            ->where('is_settled', false)
            ->get();

        $todayDelivered = Order::where('delivery_id', $id)
            ->where('callcenter_id', auth()->id())
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', today()->startOfDay())
            ->get();

        return response()->json([
            'delivery'   => ['id' => $delivery->id, 'name' => $delivery->name],
            'orders'     => $orders->map(fn($o) => [
                'order_number' => $o->order_number,
                'client'       => $o->client?->name ?? '—',
                'total'        => $o->total,
                'delivery_fee' => $o->delivery_fee,
                'delivered_at' => $o->delivered_at?->toIso8601String(),
            ]),
            'summary' => [
                'count'           => $todayDelivered->count(),
                'total_values'    => $todayDelivered->sum('total'),
                'total_fees'      => $todayDelivered->sum('delivery_fee'),
                'unsettled_value' => $orders->sum('total'),
                'unsettled_fees'  => $orders->sum('delivery_fee'),
            ],
        ]);
    }

    public function doSettlement($id)
    {
        $delivery = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);
        
        $orders = Order::where('delivery_id', $id)
            ->where('callcenter_id', auth()->id())
            ->where('status', 'delivered')
            ->where('is_settled', false)
            ->get();

        $totalValue = $orders->sum('total');
        $totalFees  = $orders->sum('delivery_fee');

        if ($orders->isNotEmpty()) {
            Order::whereIn('id', $orders->pluck('id'))->update(['is_settled' => true]);

            // Decrement the global driver accounts by the amounts just settled
            $delivery->decrement('unsettled_value', $totalValue);
            $delivery->decrement('unsettled_fees', $totalFees);

            ActivityLog::log(
                event: 'settlement.cc_delivery',
                description: 'تسوية مندوب مع كول سنتر — ' . $delivery->name,
                subjectType: 'settlement',
                subjectId: $delivery->id,
                subjectLabel: $delivery->name,
                properties: [
                    'delivery_name' => $delivery->name,
                    'orders_count'  => $orders->count(),
                    'total_value'   => $totalValue,
                    'total_fees'    => $totalFees,
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'تمت التسوية بنجاح وتصفير العهدة']);
    }
}
