<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->header('X-SPA-Navigation')) {
            $deliveries   = User::where('role', 'delivery')->orderBy('name')->get(['id', 'name']);
            $callcenters  = User::where('role', 'callcenter')->orderBy('name')->get(['id', 'name']);
            return response()->json([
                'html'       => view('admin.orders.partials.content', compact('deliveries', 'callcenters'))->render(),
                'title'      => 'الطلبات',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $query = Order::with(['client', 'callcenter', 'delivery', 'items.shop'])
                ->withCount('items')
                ->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->to);
            }
            if ($request->filled('delivery_id')) {
                $query->where('delivery_id', $request->delivery_id);
            }
            if ($request->filled('callcenter_id')) {
                $query->where('callcenter_id', $request->callcenter_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%$search%")
                      ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%"));
                });
            }

            $perPage = min((int) $request->get('per_page', 15), 5000);
            $orders = $query->paginate($perPage);
            return response()->json($orders);
        }

        $deliveries   = User::where('role', 'delivery')->orderBy('name')->get(['id', 'name']);
        $callcenters  = User::where('role', 'callcenter')->orderBy('name')->get(['id', 'name']);
        return view('admin.orders.index', compact('deliveries', 'callcenters'));
    }

    public function show($id)
    {
        $order = Order::with(['client', 'callcenter', 'delivery', 'items.shop', 'logs.user'])
            ->findOrFail($id);

        return response()->json([
            'order' => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'status'         => $order->status,
                'notes'          => $order->notes,
                'client_address' => $order->client_address,
                'send_to_phone'  => $order->send_to_phone,
                'send_to_address'=> $order->send_to_address,
                'delivery_fee'   => $order->delivery_fee,
                'discount'       => $order->discount,
                'discount_type'  => $order->discount_type,
                'total'          => $order->total,
                'created_at'     => $order->created_at->toIso8601String(),
                'client'         => $order->client ? ['name' => $order->client->name, 'phone' => $order->client->phone] : null,
                'callcenter'     => $order->callcenter ? ['name' => $order->callcenter->name] : null,
                'delivery'       => $order->delivery ? ['name' => $order->delivery->name] : null,
                'items'          => $order->items->map(fn($item) => [
                    'item_name'  => $item->item_name,
                    'shop'       => $item->shop?->name ?? '—',
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total'      => $item->total,
                ]),
                'logs'           => $order->logs->map(fn($l) => [
                    'user'       => $l->user?->name ?? 'النظام',
                    'action'     => $l->action,
                    'notes'      => $l->notes,
                    'created_at' => $l->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'يجب كتابة سبب الإلغاء',
        ]);

        $order = Order::findOrFail($id);

        if ($order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'الطلب ملغي بالفعل'], 422);
        }
        if ($order->status === 'delivered') {
            return response()->json(['success' => false, 'message' => 'لا يمكن إلغاء طلب تم توصيله'], 422);
        }

        $order->update(['status' => 'cancelled']);

        $notif = \App\Models\AdminNotification::create([
            'type'         => 'cancelled',
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'message'      => "تم إلغاء الطلب #{$order->order_number}",
        ]);
        event(new \App\Events\AdminNotificationCreated($notif));

        OrderLog::create([
            'order_id' => $order->id,
            'user_id'  => auth()->id(),
            'action'   => 'إلغاء الطلب',
            'notes'    => 'سبب الإلغاء: ' . $request->reason . ' — بواسطة الأدمن: ' . auth()->user()->name,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إلغاء الطلب']);
    }

    public function exportPdf(Request $request)
    {
        $query = Order::with(['client', 'callcenter', 'delivery'])->latest();

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('from'))          $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))            $query->whereDate('created_at', '<=', $request->to);
        if ($request->filled('delivery_id'))   $query->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id')) $query->where('callcenter_id', $request->callcenter_id);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%$search%")
                  ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%"));
            });
        }

        $orders  = $query->get();
        $filters = $request->only(['from', 'to', 'status']);

        $html = view('admin.pdf.orders', compact('orders', 'filters'))->render();
        $Arabic = new \ArPHP\I18N\Arabic();
        $p = $Arabic->arIdentify($html);
        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i-1], $p[$i] - $p[$i-1]));
            $html = substr_replace($html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download('orders-' . now()->format('Y-m-d') . '.pdf');
    }

    public function downloadPdf($id)
    {
        $order = Order::with(['client', 'callcenter', 'delivery', 'items.shop', 'logs.user'])
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pdf.order_single', compact('order'))->setPaper('a4', 'portrait');

        return $pdf->download($order->order_number . '.pdf');
    }
}
