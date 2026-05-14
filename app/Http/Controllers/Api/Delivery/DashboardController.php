<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/delivery/dashboard
     * Return summary statistics for the authenticated delivery user's current business day.
     */
    public function data(Request $request)
    {
        $delivery = $request->user();
        [$start, $end] = Setting::businessDayRange();

        // Active (open) shift — ended_at IS NULL
        $activeShift = Shift::where('delivery_id', $delivery->id)
            ->whereNull('ended_at')
            ->first();

        // Pending orders not yet accepted by anyone (or assigned to this delivery)
        $newOrders = Order::where('status', 'pending')
            ->where('sent_to_delivery_at', '<=', now())
            ->where(function ($q) use ($delivery) {
                $q->whereNull('delivery_id')
                  ->orWhere('delivery_id', $delivery->id);
            })
            ->count();

        // Orders currently held (received) by this delivery
        $activeOrders = Order::where('delivery_id', $delivery->id)
            ->where('status', 'received')
            ->count();

        // Delivered today
        $deliveredToday = Order::where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$start, $end])
            ->count();

        // Cancelled today (Includes orders cancelled by delivery OR received orders cancelled by admin)
        $cancelledToday = Order::where('delivery_id', $delivery->id)
            ->where('status', 'cancelled')
            ->whereNotNull('accepted_at')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        // Delivery fees earned today (from delivered orders)
        $feesToday = Order::where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$start, $end])
            ->sum('delivery_fee');

        // NEW: Daily Profit & Current Tier
        $profitToday = Order::where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$start, $end])
            ->sum('delivery_profit');

        $currentTier = Order::where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$start, $end])
            ->max('delivery_tier_number') ?? 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'shift_active'    => (bool) $activeShift,
                'shift_id'        => $activeShift?->id,
                'shift_started'   => $activeShift?->started_at?->toIso8601String(),
                'new_orders'      => $newOrders,
                'active_orders'   => $activeOrders,
                'delivered_today' => $deliveredToday,
                'cancelled_today' => $cancelledToday,
                'fees_today'      => (float) $feesToday,
                'profit_today'    => (float) $profitToday,
                'current_tier'    => (int) $currentTier,
            ],
        ]);
    }
}
