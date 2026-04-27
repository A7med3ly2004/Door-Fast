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
        $from   = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange(today()->subDays(30))[0];
        $to     = $request->filled('to')   ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1]     : \App\Models\Setting::businessDayRange(today())[1];
        $shopId = $request->shop_id;

        // Global KPIs
        $allShops       = Shop::count();
        
        $allItems       = OrderItem::whereNotNull('shop_id')
            ->whereHas('order', function($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to])
                  ->where('status', 'delivered');
            });
            
        $totalPurchases = $allItems->clone()->sum('total');
        $totalOrders    = Order::whereBetween('created_at', [$from, $to])
            ->where('status', 'delivered')
            ->whereHas('items', fn($q) => $q->whereNotNull('shop_id'))
            ->count();

        $topShop = OrderItem::whereNotNull('shop_id')
            ->whereHas('order', function($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to])
                  ->where('status', 'delivered');
            })
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
            'top_shop'       => ($topShop && $topShop->shop) ? ($topShop->shop->name . ' (' . number_format((float) $topShop->revenue, 2) . ' ج)') : '—',
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

            $deliveredOrders = $shopOrders->where('status', 'delivered');
            $shopKpis = [
                'orders'    => $shopOrders->count(),
                'completed' => $deliveredOrders->count(),
                'cancelled' => $shopOrders->where('status', 'cancelled')->count(),
                'pending'   => $shopOrders->whereIn('status', ['pending', 'received', 'received_by_delivery'])->count(),
                'revenue'   => $deliveredOrders->flatMap->items->sum('total'),
                'avg_order' => $deliveredOrders->count() > 0 ? round($deliveredOrders->flatMap->items->sum('total') / $deliveredOrders->count(), 2) : 0,
            ];

            // Daily chart
            $days  = (int) $from->diffInDays($to) + 1;
            $chart = [];
            for ($i = 0; $i < min($days, 60); $i++) {
                $calDay = Carbon::parse($request->filled('from') ? $request->from : today()->subDays(30))->addDays($i);
                list($dStart, $dEnd) = \App\Models\Setting::businessDayRange($calDay);
                $dayCount = $shopOrders->filter(fn($o) => $o->created_at->between($dStart, $dEnd))->count();
                $chart[]  = ['label' => $calDay->format('m/d'), 'count' => $dayCount];
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


            // Orders table
            $ordersTable = $shopOrders->map(fn($o) => [
                'id'           => $o->id,
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
                'orders'      => $ordersTable,
            ]);
        }

        return response()->json(['global' => $global]);
    }

    public function exportPdf(Request $request, $shopId)
    {
        $shop = Shop::findOrFail($shopId);

        $from = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange(today()->subDays(30))[0];
        $to   = $request->filled('to')   ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1]     : \App\Models\Setting::businessDayRange(today())[1];

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

    public function dueInvoicePdf(Request $request, $shopId)
    {
        $shop = Shop::findOrFail($shopId);

        $from = $request->filled('from') ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0] : \App\Models\Setting::businessDayRange(today()->subDays(30))[0];
        $to   = $request->filled('to')   ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1]     : \App\Models\Setting::businessDayRange(today())[1];

        // Only delivered orders
        $items = OrderItem::where('shop_id', $shopId)
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->where('status', 'delivered'))
            ->selectRaw('item_name, shop_id, unit_price, SUM(quantity) as total_qty, SUM(total) as total_value')
            ->groupBy('item_name', 'shop_id', 'unit_price')
            ->with('shop:id,name')
            ->get();

        $revenue = $items->sum('total_value');
        $discountPercent = (float) $request->input('discount_percent', 0);
        $discountValue = $revenue * ($discountPercent / 100);
        $finalAmount = $revenue - $discountValue;

        $filters = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];

        $pdf = Pdf::loadView('admin.pdf.shop_due_invoice', compact(
            'shop', 'items', 'revenue', 'discountPercent', 'discountValue', 'finalAmount', 'filters'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('due-invoice-' . $shop->name . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
