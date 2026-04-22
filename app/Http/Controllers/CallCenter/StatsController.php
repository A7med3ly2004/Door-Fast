<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('callcenter.stats.partials.content')->render(),
                'title'      => 'إحصائياتي',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('callcenter.stats.index');
    }

    public function data()
    {
        $me    = auth()->id();
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();

        $ordersToday    = Order::where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])->count();
        $deliveredToday = Order::where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])->where('status', 'delivered')->count();
        $cancelledToday = Order::where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])->where('status', 'cancelled')->count();
        $revenueToday   = Order::where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])->where('status', 'delivered')->sum('total');
        $feesToday      = Order::where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])->where('status', 'delivered')->sum('delivery_fee');
        $discountToday  = Order::where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])->sum('discount');

        // Bar chart: last 7 days
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayRange = \App\Models\Setting::businessDayRange($day);
            $chart[] = [
                'label' => $day->format('d/m'),
                'count' => Order::where('callcenter_id', $me)->whereBetween('created_at', $dayRange)->count(),
            ];
        }

        // Per-delivery breakdown (from my orders only)
        $deliveries = User::where('role', 'delivery')
            ->with(['deliveryOrders' => fn($q) => $q->where('callcenter_id', $me)->whereBetween('created_at', [$startOfToday, $endOfToday])])
            ->get()
            ->map(fn($d) => [
                'name'      => $d->name,
                'total'     => $d->deliveryOrders->count(),
                'delivered' => $d->deliveryOrders->where('status', 'delivered')->count(),
                'cancelled' => $d->deliveryOrders->where('status', 'cancelled')->count(),
                'revenue'   => $d->deliveryOrders->where('status', 'delivered')->sum('total'),
                'fees'      => $d->deliveryOrders->where('status', 'delivered')->sum('delivery_fee'),
            ])
            ->filter(fn($d) => $d['total'] > 0)
            ->values();

        return response()->json([
            'kpis' => compact('ordersToday', 'deliveredToday', 'cancelledToday', 'revenueToday', 'feesToday', 'discountToday'),
            'chart'      => $chart,
            'deliveries' => $deliveries,
        ]);
    }
}
