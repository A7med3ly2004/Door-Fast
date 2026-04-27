<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $deliveries  = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $callcenters = User::where('role', 'callcenter')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.reports.partials.content', compact('deliveries', 'callcenters'))->render(),
                'title'      => 'التقارير',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.reports.index', compact('deliveries', 'callcenters'));
    }

    public function data(Request $request)
    {
        $from = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange(today()->subDays(30))[0];
        $to   = $request->filled('to')   ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1]     : \App\Models\Setting::businessDayRange(today())[1];

        $query = Order::with(['client', 'callcenter', 'delivery'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('delivery_id'))   $query->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id')) $query->where('callcenter_id', $request->callcenter_id);

        $orders = $query->latest()->get();

        // KPIs
        $kpis = [
            'total'     => $orders->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
            'pending'   => $orders->where('status', 'pending')->count(),
            'revenue'   => $orders->where('status', 'delivered')->sum('total'),
        ];

        // Daily chart
        $days   = (int) $from->diffInDays($to) + 1;
        $chart  = [];
        for ($i = 0; $i < min($days, 60); $i++) {
            $calDay = Carbon::parse($request->filled('from') ? $request->from : today()->subDays(30))->addDays($i);
            list($dStart, $dEnd) = \App\Models\Setting::businessDayRange($calDay);
            $dayOrders = $orders->filter(fn($o) => $o->created_at->between($dStart, $dEnd));
            $chart[] = [
                'label'     => $calDay->format('m/d'),
                'count'     => $dayOrders->count(),
                'revenue'   => $dayOrders->where('status', 'delivered')->sum('total'),
            ];
        }

        // Delivery breakdown
        $deliveryBreakdown = $orders->groupBy('delivery_id')->map(function ($group, $deliveryId) {
            $first     = $group->first();
            $total     = $group->count();
            $completed = $group->where('status', 'delivered')->count();
            return [
                'name'        => $first->delivery?->name ?? 'غير مُعيَّن',
                'total'       => $total,
                'completed'   => $completed,
                'cancelled'   => $group->where('status', 'cancelled')->count(),
                'revenue'     => $group->where('status', 'delivered')->sum('total'),
            ];
        })->values();

        // Callcenter breakdown
        $ccBreakdown = $orders->groupBy('callcenter_id')->map(function ($group) {
            $first = $group->first();
            return [
                'name'      => $first->callcenter?->name ?? 'غير مُعيَّن',
                'total'     => $group->count(),
                'cancelled' => $group->where('status', 'cancelled')->count(),
                'revenue'   => $group->where('status', 'delivered')->sum('total'),
            ];
        })->values();

        // Paginated orders table
        $page    = $request->get('page', 1);
        $perPage = 20;
        $sliced  = $orders->forPage($page, $perPage)->values();
        $mapped  = $sliced->map(fn($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'created_at'   => $o->created_at->toIso8601String(),
            'client'       => $o->client?->name ?? '—',
            'callcenter'   => $o->callcenter?->name ?? '—',
            'delivery'     => $o->delivery?->name ?? '—',
            'delivery_fee' => $o->delivery_fee,
            'discount'     => $o->discount,
            'total'        => $o->total,
            'status'       => $o->status,
        ]);

        // Totals row
        $totals = [
            'delivery_fee' => $orders->sum('delivery_fee'),
            'discount'     => $orders->sum('discount'),
            'total'        => $orders->sum('total'),
            'count'        => $orders->count(),
            'pages'        => ceil($orders->count() / $perPage),
            'page'         => (int) $page,
        ];

        return response()->json([
            'kpis'               => $kpis,
            'chart'              => $chart,
            'delivery_breakdown' => $deliveryBreakdown,
            'cc_breakdown'       => $ccBreakdown,
            'orders'             => $mapped,
            'totals'             => $totals,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $from = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange(today()->subDays(30))[0];
        $to   = $request->filled('to')   ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1]     : \App\Models\Setting::businessDayRange(today())[1];

        $query = Order::with(['client', 'callcenter', 'delivery'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('delivery_id'))   $query->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id')) $query->where('callcenter_id', $request->callcenter_id);

        $orders  = $query->latest()->get();
        $filters = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
        $totals  = [
            'revenue'      => $orders->where('status', 'delivered')->sum('total'),
            'delivery_fee' => $orders->sum('delivery_fee'),
            'discount'     => $orders->sum('discount'),
        ];

        $html = view('admin.pdf.report', compact('orders', 'filters', 'totals'))->render();
        $Arabic = new \ArPHP\I18N\Arabic();
        $p = $Arabic->arIdentify($html);
        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i-1], $p[$i] - $p[$i-1]));
            $html = substr_replace($html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download('report-' . now()->format('Y-m-d') . '.pdf');
    }
}
