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
            $deliveries   = User::whereIn('role', ['delivery', 'reserve_delivery'])->orderBy('name')->get(['id', 'name']);
            $callcenters  = User::where('role', 'callcenter')->orderBy('name')->get(['id', 'name']);
            $admins       = User::where('role', 'admin')->orderBy('name')->get(['id', 'name']);
            return response()->json([
                'html'       => view('admin.orders.partials.content', compact('deliveries', 'callcenters', 'admins'))->render(),
                'title'      => 'الطلبات',
                'csrf_token' => csrf_token(),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $query = Order::with(['client', 'recipientClient', 'callcenter', 'admin', 'delivery', 'items.shop'])
                ->withCount('items')
                ->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('from')) {
                $query->where('created_at', '>=', \App\Models\Setting::businessDayRange(\Carbon\Carbon::parse($request->from))[0]);
            }
            if ($request->filled('to')) {
                $query->where('created_at', '<=', \App\Models\Setting::businessDayRange(\Carbon\Carbon::parse($request->to))[1]);
            }
            if ($request->filled('delivery_id')) {
                $query->where('delivery_id', $request->delivery_id);
            }
            if ($request->filled('callcenter_id')) {
                $query->where('callcenter_id', $request->callcenter_id);
            }
            if ($request->filled('admin_id')) {
                $query->where('admin_id', $request->admin_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $role   = $request->get('role');

                $query->where(function ($q) use ($search, $role) {
                    $q->where('order_number', '=', $search);
                    
                    if (!$role || $role === 'primary') {
                        $q->orWhereHas('client', fn($c) => $c->where('phone', '=', $search)
                            ->orWhere('phone2', '=', $search)
                            ->orWhere('code', '=', $search));
                    }
                    
                    if (!$role || $role === 'recipient') {
                        $q->orWhere('send_to_phone', '=', $search)
                          ->orWhere('send_to_phone2', '=', $search)
                          ->orWhereHas('recipientClient', fn($c) => $c->where('phone', '=', $search)
                            ->orWhere('phone2', '=', $search)
                            ->orWhere('code', '=', $search));
                    }
                });
            }

            $perPage = min((int) $request->get('per_page', 15), 10000);
            $orders = $query->paginate($perPage);
            return response()->json($orders);
        }

        $deliveries   = User::whereIn('role', ['delivery', 'reserve_delivery'])->orderBy('name')->get(['id', 'name']);
        $callcenters  = User::where('role', 'callcenter')->orderBy('name')->get(['id', 'name']);
        $admins       = User::where('role', 'admin')->orderBy('name')->get(['id', 'name']);
        return view('admin.orders.index', compact('deliveries', 'callcenters', 'admins'));
    }

    public function show($id)
    {
        $order = Order::with(['client', 'recipientClient', 'callcenter', 'delivery', 'items.shop', 'logs.user'])
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
                'client'           => $order->client ? ['name' => $order->client->name, 'phone' => $order->client->phone, 'code' => $order->client->code] : null,
                'recipient_client' => $order->recipientClient ? [
                    'name'   => $order->recipientClient->name,
                    'phone'  => $order->recipientClient->phone,
                    'phone2' => $order->recipientClient->phone2,
                    'code'   => $order->recipientClient->code,
                ] : null,
                'callcenter'       => $order->callcenter ? ['name' => $order->callcenter->name] : null,
                'delivery'         => $order->delivery ? ['name' => $order->delivery->name] : null,
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

        try {
            $order = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $request) {
                $order = Order::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($order->status === 'cancelled') {
                    throw new \RuntimeException('الطلب ملغي بالفعل');
                }
                if ($order->status === 'delivered') {
                    throw new \RuntimeException('لا يمكن إلغاء طلب تم توصيله');
                }

                $order->update(['status' => 'cancelled']);

                // عكس أي حركات محفظة سُجّلت مسبقاً على هذا الطلب (مدين → دائن والعكس).
                // اليوم لا تُسجَّل حركات قبل التوصيل، لكن نُبقي هذا الحارس لمنع
                // تسريب مالي في حال أُضيفت "وديعة عند القبول" أو ما شابه مستقبلاً.
                $relatedTxns = \App\Models\WalletTransaction::where('order_id', $order->id)->get();
                if ($relatedTxns->isNotEmpty()) {
                    $walletService = app(\App\Services\WalletService::class);
                    foreach ($relatedTxns as $txn) {
                        $wallet = \App\Models\Wallet::find($txn->wallet_id);
                        if (!$wallet) continue;
                        $args = [
                            'wallet'      => $wallet,
                            'amount'      => (float) $txn->amount,
                            'type'        => $txn->type . '_reversal',
                            'description' => 'إلغاء طلب — عكس حركة #' . $txn->id,
                            'createdBy'   => auth()->id(),
                            'orderId'     => $order->id,
                        ];
                        if ($txn->direction === 'debit') {
                            $walletService->debit(...$args); // كان دخل → خصم
                        } else {
                            $walletService->credit(...$args); // كان خصم → إعادة
                        }
                    }
                }

                $notif = \App\Models\AdminNotification::create([
                    'type'         => 'cancelled',
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'message'      => "تم إلغاء الطلب #{$order->order_number} بواسطة أدمن : " . auth()->user()->name,
                    'audience'     => 'admin', // يظهر فقط للأدمن
                ]);
                event(new \App\Events\AdminNotificationCreated($notif));
                event(new \App\Events\OrderStatusUpdated($order));

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id'  => auth()->id(),
                    'action'   => 'إلغاء الطلب',
                    'notes'    => 'سبب الإلغاء: ' . $request->reason . ' — بواسطة الأدمن: ' . auth()->user()->name,
                ]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'تم إلغاء الطلب']);
    }

    public function exportPdf(Request $request)
    {
        $query = Order::with(['client', 'recipientClient', 'callcenter', 'admin', 'delivery'])->latest();

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('from'))          $query->where('created_at', '>=', \App\Models\Setting::businessDayRange(\Carbon\Carbon::parse($request->from))[0]);
        if ($request->filled('to'))            $query->where('created_at', '<=', \App\Models\Setting::businessDayRange(\Carbon\Carbon::parse($request->to))[1]);
        if ($request->filled('delivery_id'))   $query->where('delivery_id', $request->delivery_id);
        if ($request->filled('callcenter_id')) $query->where('callcenter_id', $request->callcenter_id);
        if ($request->filled('admin_id'))      $query->where('admin_id', $request->admin_id);
        if ($request->filled('search')) {
            $search = $request->search;
            $role   = $request->get('role');

            $query->where(function ($q) use ($search, $role) {
                $q->where('order_number', '=', $search);
                
                if (!$role || $role === 'primary') {
                    $q->orWhereHas('client', fn($c) => $c->where('phone', '=', $search)
                        ->orWhere('phone2', '=', $search)
                        ->orWhere('code', '=', $search));
                }
                
                if (!$role || $role === 'recipient') {
                    $q->orWhere('send_to_phone', '=', $search)
                      ->orWhere('send_to_phone2', '=', $search)
                      ->orWhereHas('recipientClient', fn($c) => $c->where('phone', '=', $search)
                        ->orWhere('phone2', '=', $search)
                        ->orWhere('code', '=', $search));
                }
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
        $order = Order::with(['client', 'recipientClient', 'callcenter', 'admin', 'delivery', 'items.shop', 'logs.user'])
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pdf.order_single', compact('order'))->setPaper('a4', 'portrait');

        return $pdf->download($order->order_number . '.pdf');
    }
}
