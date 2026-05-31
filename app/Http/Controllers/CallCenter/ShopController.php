<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OrderItem;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        if ($request->header('X-SPA-Navigation')) {
            $categories = \App\Models\ShopCategory::orderBy('name')->get(['id', 'name']);
            return response()->json([
                'html'       => view('callcenter.shops.partials.content', compact('categories'))->render(),
                'title'      => 'المتاجر',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            if (!$request->filled('search') && !$request->filled('category_id')) {
                return response()->json(['data' => [], 'last_page' => 1, 'current_page' => 1, 'total' => 0]);
            }

            $query = Shop::with('category')->where('is_active', true)->latest();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('code', $search)
                      ->orWhere('phone',  $search)
                      ->orWhere('phone2', $search)
                      ->orWhere('phone3', $search)
                      ->orWhere('phone4', $search);
                });
            }

            if ($request->filled('category_id')) {
                $query->where('shop_category_id', $request->category_id);
            }

            return response()->json($query->paginate(15));
        }
        $categories = \App\Models\ShopCategory::orderBy('name')->get(['id','name']);
        return view('callcenter.shops.index', compact('categories'));
    }

    public function active()
    {
        $shops = Shop::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'address']);
        return response()->json($shops);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:50|unique:shops,code',
            'phone'            => 'nullable|digits:11',
            'address'          => 'nullable|string|max:500',
            'shop_category_id' => 'required|exists:shop_categories,id',
            'notes'            => 'nullable|string',
        ]);

        // Auto-generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = Shop::generateCode();
        }

        $shop = Shop::create(array_merge($data, ['is_active' => true]));

        ActivityLog::log(
            event: 'shop.created',
            description: 'تم إضافة متجر جديد بواسطة الكول سنتر — ' . $shop->name,
            subjectType: 'shop',
            subjectId: $shop->id,
            subjectLabel: $shop->name
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة المتجر بنجاح', 'shop' => $shop]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:shop_categories,name']);
        $category = \App\Models\ShopCategory::create(['name' => $request->name]);
        return response()->json(['success' => true, 'message' => 'تم إضافة الفئة بنجاح', 'category' => $category]);
    }

    public function show(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $from = \App\Models\Setting::businessDayRange(Carbon::now()->subDays(30))[0];
        $to   = \App\Models\Setting::businessDayRange(Carbon::now())[1];

        $itemsQuery = OrderItem::where('shop_id', $id)
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to]));

        $ordersCount    = $itemsQuery->clone()->distinct('order_id')->count('order_id');
        $totalPurchases = $itemsQuery->clone()->sum('total');

        $topItems = OrderItem::where('shop_id', $id)
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to]))
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
                'phone2'         => $shop->phone2,
                'phone3'         => $shop->phone3,
                'phone4'         => $shop->phone4,
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
            'code'             => 'required|string|max:50|unique:shops,code,' . $shop->id,
            'phone'            => 'nullable|digits:11',
            'phone2'           => 'nullable|digits:11',
            'phone3'           => 'nullable|digits:11',
            'phone4'           => 'nullable|digits:11',
            'address'          => 'nullable|string|max:500',
            'shop_category_id' => 'required|exists:shop_categories,id',
            'notes'            => 'nullable|string',
        ]);

        $shop->update($data);

        ActivityLog::log(
            event: 'shop.updated',
            description: 'تم تعديل متجر بواسطة الكول سنتر — ' . $shop->name,
            subjectType: 'shop',
            subjectId: $shop->id,
            subjectLabel: $shop->name
        );

        return response()->json(['success' => true, 'message' => 'تم تعديل المتجر بنجاح']);
    }
}
