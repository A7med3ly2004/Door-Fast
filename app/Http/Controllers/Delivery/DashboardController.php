<?php

namespace App\Http\Controllers\Delivery;

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
                'html'       => view('delivery.partials.dashboard_content')->render(),
                'title'      => 'إحصائياتي اليوم',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('delivery.dashboard');
    }

    public function data()
    {
        $delivery = auth()->user();
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $shift = Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->where('is_active', true)
            ->first();

        $started_at = $shift ? Carbon::parse($shift->started_at)->format('H:i') : null;
        $started_timestamp = $shift ? Carbon::parse($shift->started_at)->timestamp : null;

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

        // Removed capacity count logic

        return response()->json([
            'started_at' => $started_at,
            'started_timestamp' => $started_timestamp,
            'delivered_count' => $deliveredCount,
            'received_count' => $receivedCount,
            'cancelled_count' => $cancelledCount,
            'total_collected' => $totalCollected,
            'total_delivery_fee' => $totalDeliveryFee,
            'total_discount' => $totalDiscount,
        ]);
    }
}
