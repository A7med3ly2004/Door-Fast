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
        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $callcenters = User::where('role', 'callcenter')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.reports.partials.content', compact('deliveries', 'callcenters', 'admins'))->render(),
                'title' => 'التقارير',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.reports.index', compact('deliveries', 'callcenters', 'admins'));
    }

    public function data(Request $request)
    {
        [$currentBizStart, $currentBizEnd] = \App\Models\Setting::businessDayRange();
        $from = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange($currentBizStart->copy()->subDays(30))[0];
        $to = $request->filled('to') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1] : $currentBizEnd;

        $query = Order::with(['client', 'callcenter', 'admin', 'delivery'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('delivery_id'))
            $query->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id'))
            $query->where('callcenter_id', $request->callcenter_id);
        if ($request->filled('admin_id'))
            $query->where('admin_id', $request->admin_id);

        $orders = $query->latest()->get();

        // KPIs
        $kpis = [
            'total' => $orders->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
            'pending' => $orders->whereIn('status', ['pending', 'received_by_delivery'])->count(),
            'revenue' => $orders->where('status', 'delivered')->sum('total'),
            'delivery_fees' => $orders->where('status', 'delivered')->sum('delivery_fee'),
            'avg_delivery' => $orders->where('status', 'delivered')->count() > 0
                ? $orders->where('status', 'delivered')->sum('delivery_fee') / $orders->where('status', 'delivered')->count()
                : 0,
        ];

        // Daily chart (sliding business day) using SQL grouping
        $startHour = (int) \App\Models\Setting::get('business_day_start_hour', 0);
        $chartQuery = Order::whereBetween('created_at', [$from, $to]);
        if ($request->filled('delivery_id'))
            $chartQuery->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id'))
            $chartQuery->where('callcenter_id', $request->callcenter_id);
        if ($request->filled('admin_id'))
            $chartQuery->where('admin_id', $request->admin_id);

        $chartData = $chartQuery->selectRaw("
                DATE(DATE_SUB(created_at, INTERVAL {$startHour} HOUR)) as order_date,
                COUNT(*) as total_count,
                SUM(CASE WHEN status='delivered' THEN delivery_fee ELSE 0 END) as delivery_fees
            ")
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get();

        $chart = $chartData->map(function ($row) {
            return [
                'label' => Carbon::parse($row->order_date)->format('m/d'),
                'count' => (int) $row->total_count,
                'delivery_fees' => (float) $row->delivery_fees,
            ];
        })->toArray();

        // Delivery breakdown
        $deliveryBreakdown = $orders->groupBy('delivery_id')->map(function ($group, $deliveryId) {
            $first = $group->first();
            $total = $group->count();
            $completed = $group->where('status', 'delivered')->count();
            return [
                'name' => $first->delivery?->name ?? 'غير معين',
                'total' => $total,
                'completed' => $completed,
                'cancelled' => $group->where('status', 'cancelled')->count(),
                'revenue' => $group->where('status', 'delivered')->sum('delivery_fee'),
            ];
        })->values();

        // Callcenter breakdown
        $ccBreakdown = $orders->groupBy('callcenter_id')->map(function ($group) {
            $first = $group->first();
            return [
                'name' => $first->callcenter?->name ?? 'غير معين',
                'total' => $group->count(),
                'cancelled' => $group->where('status', 'cancelled')->count(),
                'revenue' => $group->where('status', 'delivered')->sum('total'),
            ];
        })->values();

        // Paginated orders table (per_page=9999 → export all, default 20 for UI)
        $page = $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 20), 5000);
        $sliced = $orders->forPage($page, $perPage)->values();
        $mapped = $sliced->map(fn($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'created_at' => $o->created_at->toIso8601String(),
            'client' => $o->client?->name ?? '—',
            'creator_name' => $o->callcenter?->name ?? $o->admin?->name ?? '—',
            'creator_type' => $o->callcenter ? 'cc' : ($o->admin ? 'admin' : null),
            'delivery' => $o->delivery?->name ?? '—',
            'delivery_fee' => $o->delivery_fee,
            'discount' => $o->discount,
            'total' => $o->total,
            'status' => $o->status,
        ]);

        // Totals row
        $totals = [
            'delivery_fee' => $orders->sum('delivery_fee'),
            'discount' => $orders->sum('discount'),
            'total' => $orders->sum('total'),
            'count' => $orders->count(),
            'pages' => ceil($orders->count() / $perPage),
            'page' => (int) $page,
        ];

        return response()->json([
            'kpis' => $kpis,
            'chart' => $chart,
            'delivery_breakdown' => $deliveryBreakdown,
            'cc_breakdown' => $ccBreakdown,
            'orders' => $mapped,
            'totals' => $totals,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $from = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange(today()->subDays(30))[0];
        $to = $request->filled('to') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1] : \App\Models\Setting::businessDayRange(today())[1];

        $query = Order::with(['client', 'callcenter', 'admin', 'delivery'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('delivery_id'))
            $query->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id'))
            $query->where('callcenter_id', $request->callcenter_id);
        if ($request->filled('admin_id'))
            $query->where('admin_id', $request->admin_id);

        $orders = $query->latest()->get();
        $filters = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
        $totals = [
            'revenue' => $orders->where('status', 'delivered')->sum('total'),
            'delivery_fee' => $orders->sum('delivery_fee'),
            'discount' => $orders->sum('discount'),
        ];

        $html = view('admin.pdf.report', compact('orders', 'filters', 'totals'))->render();
        $Arabic = new \ArPHP\I18N\Arabic();
        $p = $Arabic->arIdentify($html);
        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $html = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download('report-' . now()->format('Y-m-d') . '.pdf');
    }
}
