<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OrderItem;
use App\Models\Shop;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Shop::with('category')
            ->withCount(['orderItems as orders_count' => fn($q) => $q->whereHas('order')])
            ->withSum('orderItems', 'total')
            ->latest();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('shop_category_id', $request->category_id);
        }

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.shops.partials.content')->render(),
                'title'      => 'المتاجر',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json($query->paginate(15));
        }

        $shops = $query->paginate(15);
        $categories = \App\Models\ShopCategory::orderBy('name')->get();
        return view('admin.shops.index', compact('shops', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:50|unique:shops,code',
            'phone'            => 'nullable|string|max:30',
            'address'          => 'nullable|string|max:500',
            'shop_category_id' => 'required|exists:shop_categories,id',
            'notes'            => 'nullable|string',
        ]);

        $shop = Shop::create(array_merge($data, ['is_active' => true]));

        ActivityLog::log(
            event: 'shop.created',
            description: 'تم إضافة متجر جديد — ' . $shop->name,
            subjectType: 'shop',
            subjectId: $shop->id,
            subjectLabel: $shop->name
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة المتجر', 'shop' => $shop]);
    }

    public function show(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $from = $request->filled('from') ? Carbon::parse($request->from) : Carbon::now()->subDays(30);
        $to   = $request->filled('to')   ? Carbon::parse($request->to)   : Carbon::now();

        $itemsQuery = OrderItem::where('shop_id', $id)
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]));

        $ordersCount   = $itemsQuery->clone()->distinct('order_id')->count('order_id');
        $totalPurchases = $itemsQuery->clone()->sum('total');

        $topItems = OrderItem::where('shop_id', $id)
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from->startOfDay(), $to->clone()->endOfDay()]))
            ->selectRaw('item_name, SUM(quantity) as total_qty, SUM(total) as total_value')
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->take(10)
            ->get();

        return response()->json([
            'shop' => [
                'id'             => $shop->id,
                'name'           => $shop->name,
                'phone'          => $shop->phone,
                'address'        => $shop->address,
                'notes'          => $shop->notes,
                'is_active'      => $shop->is_active,
                'orders_count'   => $ordersCount,
                'total_purchases'=> $totalPurchases,
                'top_items'      => $topItems,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:50|unique:shops,code,' . $id,
            'phone'            => 'nullable|string|max:30',
            'address'          => 'nullable|string|max:500',
            'shop_category_id' => 'required|exists:shop_categories,id',
            'notes'            => 'nullable|string',
        ]);

        $shop->update($data);

        return response()->json(['success' => true, 'message' => 'تم تحديث المتجر']);
    }

    public function toggle($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->update(['is_active' => !$shop->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $shop->is_active,
            'message'   => $shop->is_active ? 'تم تفعيل المتجر' : 'تم إيقاف المتجر',
        ]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:shop_categories,name']);
        $category = \App\Models\ShopCategory::create(['name' => $request->name]);
        return response()->json(['success' => true, 'category' => $category]);
    }
}
