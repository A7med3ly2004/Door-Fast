<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Shift;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('reserve_delivery.partials.dashboard_content')->render(),
                'title'      => 'إحصائياتي اليوم',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('reserve_delivery.dashboard');
    }

    public function data()
    {
        $delivery = auth()->user();
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $allShiftsToday = Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->get();

        $previous_worked_seconds = 0;
        foreach ($allShiftsToday as $s) {
            if (!$s->is_active && $s->started_at && $s->ended_at) {
                $previous_worked_seconds += Carbon::parse($s->ended_at)->timestamp - Carbon::parse($s->started_at)->timestamp;
            }
        }

        $activeShift = $allShiftsToday->where('is_active', true)->first();

        $started_at = $activeShift ? Carbon::parse($activeShift->started_at)->format('H:i') : null;
        $started_timestamp = $activeShift ? Carbon::parse($activeShift->started_at)->timestamp : null;

        $orders = Order::where('delivery_id', $delivery->id)
            ->where(function($query) use ($startOfToday, $endOfToday) {
                $query->whereBetween('accepted_at', [$startOfToday, $endOfToday])
                      ->orWhereBetween('delivered_at', [$startOfToday, $endOfToday])
                      ->orWhereBetween('created_at', [$startOfToday, $endOfToday]);
            })
            ->get();

        $deliveredCount = $orders->where('status', 'delivered')->count();
        $receivedCount = $orders->where('status', 'received')->count();
        $cancelledCount = $orders->where('status', 'cancelled')->count();

        $deliveredOrders = $orders->where('status', 'delivered');
        $totalCollected = $deliveredOrders->sum('total');
        $totalDeliveryFee = $deliveredOrders->sum('delivery_fee');
        $totalDiscount = $deliveredOrders->sum('discount');

        $capacityCount = $deliveredCount + $receivedCount;

        return response()->json([
            'started_at' => $started_at,
            'started_timestamp' => $started_timestamp,
            'previous_worked_seconds' => $previous_worked_seconds,
            'delivered_count' => $deliveredCount,
            'received_count' => $receivedCount,
            'cancelled_count' => $cancelledCount,
            'total_collected' => $totalCollected,
            'total_delivery_fee' => $totalDeliveryFee,
            'total_discount' => $totalDiscount,
            'capacity_count' => $capacityCount,
        ]);
    }
}
