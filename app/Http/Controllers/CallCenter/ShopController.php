<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Shop;
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
            $query = Shop::with('category')->where('is_active', true)->latest();
            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('code', 'like', '%' . $request->search . '%');
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
            'phone'            => 'nullable|string|max:30',
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
}
