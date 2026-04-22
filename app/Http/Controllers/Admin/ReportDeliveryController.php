<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportDeliveryController extends Controller
{
    /**
     * Display the initial page structure and filter dependencies
     */
    public function index(Request $request)
    {
        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.report-delivery.partials.content', compact('deliveries'))->render(),
                'title' => 'تقارير المناديب',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.report-delivery.index', compact('deliveries'));
    }

    /**
     * Fetch the data (KPIs & orders datatable) via SPA JSON call
     */
    public function data(Request $request)
    {
        $request->validate([
            'delivery_id' => 'required|exists:users,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfDay();

        $deliveryId = $request->delivery_id;

        // ── 1. Optimized KPI query (single DB round-trip for aggregates)
        $kpis = Order::where('delivery_id', $deliveryId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(delivery_fee) as total_fees,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status='delivered' THEN total ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status='delivered' THEN discount ELSE 0 END) as total_discounts
            ")
            ->first();

        // ── 2. Creditor (مدين) - unsettled amounts from TreasuryTransaction
        $creditor = TreasuryTransaction::where('source_type', 'manual')
            ->where('source_id', $deliveryId)
            ->whereIn('type', ['dain'])
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        // ── 3. Debtor (دائن) - Delivered orders total (what the delivery owes back)
        $debtor = Order::where('delivery_id', $deliveryId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'delivered')
            ->sum('total');

        // ── 4. Tier & Profits (Computed in PHP, no extra queries)
        $delivery = User::find($deliveryId);
        $deliveredCount = Order::where('delivery_id', $deliveryId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'delivered')
            ->count();

        $slices = collect($delivery->incentive_slices ?? []);
        $matchedSlice = $slices->first(function ($s) use ($deliveredCount) {
            return $deliveredCount >= $s['from'] && $deliveredCount <= $s['to'];
        });

        $tierNumber = 0;
        if ($matchedSlice) {
            // Find index + 1
            foreach ($slices->values() as $index => $s) {
                if ($s === $matchedSlice) {
                    $tierNumber = $index + 1;
                    break;
                }
            }
        }
        $tierAmount = $matchedSlice['amount'] ?? 0;
        $totalProfits = $deliveredCount * $tierAmount;

        // ── 5. Datatable (Paginated, Eager Loaded)
        $orders = Order::with(['client:id,name', 'callcenter:id,name'])
            ->where('delivery_id', $deliveryId)
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->paginate(15);

        return response()->json([
            'kpis' => [
                'total_orders'    => (int) ($kpis->total_orders ?? 0),
                'total_fees'      => number_format((float) ($kpis->total_fees ?? 0), 2),
                'cancelled'       => (int) ($kpis->cancelled ?? 0),
                'total_revenue'   => number_format((float) ($kpis->total_revenue ?? 0), 2),
                'total_discounts' => number_format((float) ($kpis->total_discounts ?? 0), 2),
                'creditor'        => number_format((float) $creditor, 2),
                'debtor'          => number_format((float) $debtor, 2),
                'tier_number'     => $tierNumber,
                'total_profits'   => number_format((float) $totalProfits, 2),
            ],
            'orders' => $orders,
            'delivery_name' => $delivery->name
        ]);
    }
}
