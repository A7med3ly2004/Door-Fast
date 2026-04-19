<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportDiscountController extends Controller
{
    public function index()
    {
        $callcenters = User::where('role', 'callcenter')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.report-discounts.partials.content', compact('callcenters'))->render(),
                'title'      => 'تقارير الخصومات',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.report-discounts.index', compact('callcenters'));
    }

    /**
     * Client search — returns id, name, code for the searchable dropdown.
     */
    public function searchClients(Request $request)
    {
        $q = $request->get('q', '');

        $clients = Client::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%");
        })
        ->orderBy('name')
        ->limit(30)
        ->get(['id', 'name', 'code']);

        return response()->json($clients->map(fn($c) => [
            'id'   => $c->id,
            'text' => "[{$c->code}] {$c->name}",
        ]));
    }

    /**
     * Main data endpoint — returns KPIs + paginated discounted orders.
     */
    public function data(Request $request)
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::now()->endOfDay();

        $query = Order::with(['client', 'callcenter', 'delivery', 'items'])
            ->whereBetween('created_at', [$from, $to])
            ->where('discount', '>', 0);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('callcenter_id')) {
            $query->where('callcenter_id', $request->callcenter_id);
        }

        $orders = $query->latest()->get();

        $kpis = [
            'total_orders'    => $orders->count(),
            'total_discounts' => $orders->sum('discount'),
        ];

        // Paginate in-memory
        $page    = (int) $request->get('page', 1);
        $perPage = 25;
        $sliced  = $orders->forPage($page, $perPage)->values();

        $mapped = $sliced->map(fn($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'created_at'   => $o->created_at->toIso8601String(),
            'client'       => $o->client?->name ?? '—',
            'client_code'  => $o->client?->code ?? '—',
            'callcenter'   => $o->callcenter?->name ?? '—',
            'delivery'     => $o->delivery?->name ?? '—',
            'items_count'  => $o->items->count(),
            'discount'     => $o->discount,
            'discount_type'=> $o->discount_type,
            'total'        => $o->total,
            'status'       => $o->status,
        ]);

        $totals = [
            'count'           => $orders->count(),
            'total_discounts' => $orders->sum('discount'),
            'total_revenue'   => $orders->sum('total'),
            'pages'           => (int) ceil($orders->count() / $perPage),
            'page'            => $page,
        ];

        return response()->json([
            'kpis'   => $kpis,
            'orders' => $mapped,
            'totals' => $totals,
        ]);
    }

    /**
     * Single order details for the modal.
     */
    public function orderDetail($id)
    {
        $order = Order::with(['client', 'callcenter', 'delivery', 'items'])->findOrFail($id);

        return response()->json([
            'order_number'  => $order->order_number,
            'created_at'    => $order->created_at->toIso8601String(),
            'status'        => $order->status,
            'client'        => $order->client?->name ?? '—',
            'client_code'   => $order->client?->code ?? '—',
            'callcenter'    => $order->callcenter?->name ?? '—',
            'delivery'      => $order->delivery?->name ?? '—',
            'discount'      => $order->discount,
            'discount_type' => $order->discount_type,
            'delivery_fee'  => $order->delivery_fee,
            'total'         => $order->total,
            'notes'         => $order->notes,
            'items'         => $order->items->map(fn($i) => [
                'item_name'  => $i->item_name,
                'quantity'   => $i->quantity,
                'unit_price' => $i->unit_price,
                'total'      => $i->total,
            ]),
        ]);
    }
}
