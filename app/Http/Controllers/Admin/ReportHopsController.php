<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportHopsController extends Controller
{
    public function index()
    {
        $shops = Shop::orderBy('name')->get(['id', 'name', 'is_active']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.report-hops.partials.content', compact('shops'))->render(),
                'title'      => 'تقارير الهوبز',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.report-hops.index', compact('shops'));
    }

    public function data(Request $request)
    {
        $from   = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to     = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : Carbon::now()->endOfDay();
        $shopId = $request->shop_id;

        // Global KPIs
        $allShops       = Shop::count();
        $allItems       = OrderItem::whereBetween('created_at', [$from, $to]);
        $allOrders      = Order::whereBetween('created_at', [$from, $to]);
        $totalPurchases = $allItems->clone()->sum('total');
        $totalOrders    = $allItems->clone()->distinct('order_id')->count('order_id');

        $topShop = OrderItem::whereBetween('created_at', [$from, $to])
            ->selectRaw('shop_id, SUM(total) as revenue')
            ->groupBy('shop_id')
            ->orderByDesc('revenue')
            ->with('shop:id,name')
            ->first();

        $avgOrderValue = $totalOrders > 0 ? round($totalPurchases / $totalOrders, 2) : 0;

        $global = [
            'total_shops'    => $allShops,
            'total_orders'   => $totalOrders,
            'total_purchases'=> $totalPurchases,
            'top_shop'       => $topShop?->shop?->name ?? '—',
            'avg_order'      => $avgOrderValue,
        ];

        // Shop detail
        if ($shopId) {
            $shop       = Shop::findOrFail($shopId);
            $shopOrders = Order::whereHas('items', fn($q) => $q->where('shop_id', $shopId))
                ->with(['client', 'delivery', 'callcenter', 'items' => fn($q) => $q->where('shop_id', $shopId)])
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->get();

            $shopKpis = [
                'orders'    => $shopOrders->count(),
                'completed' => $shopOrders->where('status', 'delivered')->count(),
                'cancelled' => $shopOrders->where('status', 'cancelled')->count(),
                'pending'   => $shopOrders->where('status', 'pending')->count(),
                'revenue'   => $shopOrders->flatMap->items->sum('total'),
                'avg_order' => $shopOrders->count() > 0 ? round($shopOrders->flatMap->items->sum('total') / $shopOrders->count(), 2) : 0,
            ];

            // Daily chart
            $days  = (int) $from->diffInDays($to) + 1;
            $chart = [];
            for ($i = 0; $i < min($days, 60); $i++) {
                $day = $from->clone()->addDays($i);
                $dayCount = $shopOrders->filter(fn($o) => $o->created_at->isSameDay($day))->count();
                $chart[]  = ['label' => $day->format('m/d'), 'count' => $dayCount];
            }

            // Top clients
            $topClients = $shopOrders->groupBy('client_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'name'    => $first->client?->name ?? '—',
                    'orders'  => $group->count(),
                    'spend'   => $group->flatMap->items->sum('total'),
                ];
            })->sortByDesc('orders')->take(10)->values();

            // Top items
            $itemsRaw = OrderItem::where('shop_id', $shopId)
                ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to]))
                ->selectRaw('item_name, SUM(quantity) as total_qty, SUM(total) as total_value, AVG(unit_price) as avg_price')
                ->groupBy('item_name')
                ->orderByDesc('total_qty')
                ->get();

            $totalQtyAll = $itemsRaw->sum('total_qty') ?: 1;
            $topItems = $itemsRaw->map(fn($item) => [
                'name'        => $item->item_name,
                'qty'         => $item->total_qty,
                'value'       => $item->total_value,
                'avg_price'   => round($item->avg_price, 2),
                'percentage'  => round($item->total_qty / $totalQtyAll * 100, 1),
            ]);

            // Orders table
            $ordersTable = $shopOrders->map(fn($o) => [
                'order_number' => $o->order_number,
                'created_at'   => $o->created_at->toIso8601String(),
                'client'       => $o->client?->name ?? '—',
                'delivery'     => $o->delivery?->name ?? '—',
                'callcenter'   => $o->callcenter?->name ?? '—',
                'items_count'  => $o->items->count(),
                'total'        => $o->items->sum('total'),
                'status'       => $o->status,
            ]);

            return response()->json([
                'global'      => $global,
                'shop'        => ['id' => $shop->id, 'name' => $shop->name, 'phone' => $shop->phone, 'address' => $shop->address],
                'shop_kpis'   => $shopKpis,
                'chart'       => $chart,
                'top_clients' => $topClients,
                'top_items'   => $topItems,
                'items_summary' => [
                    'total_qty'   => $itemsRaw->sum('total_qty'),
                    'total_value' => $itemsRaw->sum('total_value'),
                    'avg_price'   => $itemsRaw->count() > 0 ? round($itemsRaw->avg('avg_price'), 2) : 0,
                ],
                'orders'      => $ordersTable,
            ]);
        }

        return response()->json(['global' => $global]);
    }

    public function exportPdf(Request $request, $shopId)
    {
        $shop = Shop::findOrFail($shopId);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : Carbon::now()->endOfDay();

        $orders = Order::whereHas('items', fn($q) => $q->where('shop_id', $shopId))
            ->with(['client', 'items' => fn($q) => $q->where('shop_id', $shopId)])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $topItems = OrderItem::where('shop_id', $shopId)
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw('item_name, SUM(quantity) as total_qty, SUM(total) as total_value')
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->get();

        $filters = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];

        $html = view('admin.pdf.shop-report', compact('shop', 'orders', 'topItems', 'filters'))->render();
        $Arabic = new \ArPHP\I18N\Arabic();
        $p = $Arabic->arIdentify($html);
        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i-1], $p[$i] - $p[$i-1]));
            $html = substr_replace($html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->download('shop-report-' . $shop->name . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
