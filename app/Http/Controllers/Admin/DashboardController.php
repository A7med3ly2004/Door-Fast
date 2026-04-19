<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use App\Models\OrderLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.dashboard.partials.content')->render(),
                'title'      => 'لوحة التحكم',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view('admin.dashboard.index');
    }

    public function stats()
    {
        $today = today();

        $ordersToday      = Order::whereDate('created_at', $today)->count();
        $completedToday   = Order::whereDate('created_at', $today)->where('status', 'delivered')->count();
        $pendingToday     = Order::whereDate('created_at', $today)->where('status', 'pending')->count();
        $cancelledToday   = Order::whereDate('created_at', $today)->where('status', 'cancelled')->count();
        $dailyRevenue     = Order::whereDate('created_at', $today)->where('status', 'delivered')->sum('total');
        $monthlyRevenue   = Order::whereYear('created_at', $today->year)
                                 ->whereMonth('created_at', $today->month)
                                 ->where('status', 'delivered')->sum('total');
        $totalClients     = Client::count();

        // Bar chart: last 7 days
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $chartData[] = [
                'date'  => $day->format('m/d'),
                'label' => $day->locale('ar')->isoFormat('ddd D/M'),
                'count' => Order::whereDate('created_at', $day)->count(),
            ];
        }

        // Delivery performance today
        $deliveryPerf = User::where('role', 'delivery')
            ->with(['deliveryOrders' => fn($q) => $q->whereDate('created_at', $today)])
            ->get()
            ->map(fn($d) => [
                'name'      => $d->name,
                'completed' => $d->deliveryOrders->where('status', 'delivered')->count(),
                'revenue'   => $d->deliveryOrders->where('status', 'delivered')->sum('total'),
            ])
            ->filter(fn($d) => $d['completed'] > 0)
            ->values();

        // Callcenter performance today
        $ccPerf = User::where('role', 'callcenter')
            ->with(['createdOrders' => fn($q) => $q->whereDate('created_at', $today)])
            ->get()
            ->map(fn($cc) => [
                'name'      => $cc->name,
                'created'   => $cc->createdOrders->count(),
                'cancelled' => $cc->createdOrders->where('status', 'cancelled')->count(),
                'revenue'   => $cc->createdOrders->where('status', 'delivered')->sum('total'),
            ])
            ->filter(fn($cc) => $cc['created'] > 0)
            ->values();

        return response()->json([
            'kpis' => [
                'orders_today'    => $ordersToday,
                'completed_today' => $completedToday,
                'pending_today'   => $pendingToday,
                'cancelled_today' => $cancelledToday,
                'daily_revenue'   => $dailyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'total_clients'   => $totalClients,
            ],
            'chart'        => $chartData,
            'delivery_perf'=> $deliveryPerf,
            'cc_perf'      => $ccPerf,
        ]);
    }

    public function recentOrders()
    {
        $orders = Order::with(['client', 'callcenter', 'delivery'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($o) => [
                'id'             => $o->id,
                'order_number'   => $o->order_number,
                'client'         => $o->client?->name ?? '—',
                'callcenter'     => $o->callcenter?->name ?? '—',
                'delivery'       => $o->delivery?->name ?? '—',
                'total'          => $o->total,
                'status'         => $o->status,
                'created_at'     => $o->created_at->toIso8601String(),
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function activity()
    {
        $logs = OrderLog::with(['order', 'user'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($l) => [
                'order_number' => $l->order?->order_number ?? '—',
                'user'         => $l->user?->name ?? 'النظام',
                'action'       => $l->action,
                'notes'        => $l->notes,
                'created_at'   => $l->created_at->toIso8601String(),
            ]);

        return response()->json(['logs' => $logs]);
    }
}