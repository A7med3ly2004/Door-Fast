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
                SUM(CASE WHEN status='delivered' THEN delivery_fee ELSE 0 END) as total_fees,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status='delivered' THEN total ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status='delivered' THEN discount ELSE 0 END) as total_discounts
            ")
            ->first();

        // ── 2. Wallet Ledger for Debtor/Creditor
        $wallet = \App\Models\Wallet::where('user_id', $deliveryId)->first();
        $totalDebit = 0;
        $totalCredit = 0;

        if ($wallet) {
            $walletTx = \App\Models\WalletTransaction::where('wallet_id', $wallet->id);
            if ($request->filled('from')) {
                $walletTx->where('transaction_date', '>=', $from->toDateString());
            }
            if ($request->filled('to')) {
                $walletTx->where('transaction_date', '<=', $to->toDateString());
            }
            
            $totals = $walletTx->selectRaw("
                    COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                    COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
                ")->first();
            
            $totalDebit = $totals->total_debit;
            $totalCredit = $totals->total_credit;
        }

        $debtor   = $totalDebit;
        $creditor = $totalCredit;
        $periodSafeBalance = $totalDebit - $totalCredit;

        // ── 3. Work Hours & Days
        $shifts = \App\Models\Shift::where('delivery_id', $deliveryId)
            ->whereBetween('started_at', [$from, $to])
            ->get();
            
        $totalWorkSeconds = 0;
        foreach ($shifts as $shift) {
            $start = $shift->started_at;
            if (!$start) continue;

            $end = $shift->ended_at;
            if (!$end) {
                // للورديات المفتوحة التي نسي المندوب إغلاقها:
                // يتم حساب الوقت حتى اللحظة الحالية (now)
                // بحد أقصى نهاية فترة التقرير ($to) أو نهاية يوم بداية الوردية (أيهما أقرب)
                // لمنع تراكم الساعات لأيام طويلة.
                $end = now()->min($to)->min($start->copy()->endOfDay());
            }

            $seconds = $end->getTimestamp() - $start->getTimestamp();
            if ($seconds > 0) {
                $totalWorkSeconds += $seconds;
            }
        }
        $totalWorkHours = floor($totalWorkSeconds / 3600);
        $totalWorkMinutes = floor(($totalWorkSeconds % 3600) / 60);
        $formattedWorkHours = sprintf('%02d:%02d', $totalWorkHours, $totalWorkMinutes);

        $totalWorkDays = \App\Models\Shift::where('delivery_id', $deliveryId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->distinct('date')
            ->count('date');

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
                'period_safe_balance' => number_format((float) $periodSafeBalance, 2),
                'raw_period_safe_balance' => $periodSafeBalance,
                'tier_number'     => $tierNumber,
                'total_profits'   => number_format((float) $totalProfits, 2),
                'total_work_hours' => $formattedWorkHours,
                'total_work_days'  => $totalWorkDays,
            ],
            'orders' => $orders,
            'delivery_name' => $delivery->name
        ]);
    }
}
