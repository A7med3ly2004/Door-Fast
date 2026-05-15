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

        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.report-discounts.partials.content', compact('callcenters', 'admins'))->render(),
                'title'      => 'تقارير الخصومات',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.report-discounts.index', compact('callcenters', 'admins'));
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
            ? \App\Models\Setting::businessDayRange(Carbon::parse($request->from))[0]
            : \App\Models\Setting::businessDayRange(now()->subDays(30))[0];

        $to = $request->filled('to')
            ? \App\Models\Setting::businessDayRange(Carbon::parse($request->to))[1]
            : \App\Models\Setting::businessDayRange(now())[1];

        $query = Order::with(['client', 'callcenter', 'admin', 'delivery', 'items'])
            ->whereBetween('created_at', [$from, $to])
            ->where('discount', '>', 0)
            ->where('status', 'delivered');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('client', function ($cq) use ($searchTerm) {
                      $cq->where('code', 'like', "%{$searchTerm}%")
                         ->orWhere('phone', 'like', "%{$searchTerm}%")
                         ->orWhere('phone2', 'like', "%{$searchTerm}%");
                  });
            });
        }
        if ($request->filled('callcenter_id')) {
            $query->where('callcenter_id', $request->callcenter_id);
        }
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        $orders = $query->latest()->get();

        $kpis = [
            'total_orders'    => $orders->count(),
            'total_discounts' => $orders->sum('discount'),
        ];

        // Paginate in-memory (per_page=9999 → export all, default 25 for UI)
        $page    = (int) $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 25), 5000);
        $sliced  = $orders->forPage($page, $perPage)->values();

        $mapped = $sliced->map(fn($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'created_at'   => $o->created_at->toIso8601String(),
            'client'       => $o->client?->name ?? '—',
            'client_code'  => $o->client?->code ?? '—',
            'callcenter'   => $o->callcenter?->name ?? $o->admin?->name ?? '—',
            'creator_type' => $o->callcenter ? 'cc' : ($o->admin ? 'admin' : null),
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
            'order' => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'status'         => $order->status,
                'notes'          => $order->notes,
                'client_address' => $order->client_address,
                'delivery_fee'   => $order->delivery_fee,
                'discount'       => $order->discount,
                'discount_type'  => $order->discount_type,
                'total'          => $order->total,
                'created_at'     => $order->created_at->toIso8601String(),
                'client'         => $order->client ? ['name' => $order->client->name, 'phone' => $order->client->phone, 'code' => $order->client->code] : null,
                'callcenter'     => $order->callcenter ? ['name' => $order->callcenter->name] : ($order->admin ? ['name' => $order->admin->name] : null),
                'delivery'       => $order->delivery ? ['name' => $order->delivery->name] : null,
                'items'          => $order->items->map(fn($item) => [
                    'item_name'  => $item->item_name,
                    'shop'       => $item->shop?->name ?? '—',
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total'      => $item->total,
                ]),
                'logs'           => [], // تقارير الخصومات عادة لا تحتاج السجل الكامل هنا، لكن نتركه فارغاً للتوافق
            ],
        ]);
    }
}
