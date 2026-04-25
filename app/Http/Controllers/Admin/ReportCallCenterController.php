<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportCallCenterController extends Controller
{
    /**
     * Display the initial page structure and filter dependencies
     */
    public function index(Request $request)
    {
        $callcenters = User::callcenters()->active()->orderBy('name')->get(['id', 'name']);

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.report-callcenter.partials.content', compact('callcenters'))->render(),
                'title' => 'تقارير الكول سنتر',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.report-callcenter.index', compact('callcenters'));
    }

    /**
     * Fetch the data (KPIs & orders datatable) via SPA JSON call
     */
    public function data(Request $request)
    {
        $request->validate([
            'callcenter_id' => 'required|exists:users,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfDay();

        $callcenterId = $request->callcenter_id;

        // ── 1. Optimized KPI query
        $kpis = Order::where('callcenter_id', $callcenterId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as total_received,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status IN ('pending', 'received_by_delivery') THEN 1 ELSE 0 END) as total_pending,
                SUM(CASE WHEN status='delivered' THEN delivery_fee ELSE 0 END) as total_fees,
                SUM(CASE WHEN status='delivered' THEN discount ELSE 0 END) as total_discounts
            ")
            ->first();

        // ── 2. Total Delivered Orders Value (إجمالي الطلبات الموصلة)
        $totalDeliveredRevenue = Order::where('callcenter_id', $callcenterId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'delivered')
            ->sum('total');

        // ── 3. Ledger/Wallet logic 
        $wallet = \App\Models\Wallet::where('user_id', $callcenterId)->first();
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

        // ── 4. Period Safe Balance
        $periodSafeBalance = $totalDebit - $totalCredit;

        // ── 4.5 Work Hours & Days
        $shifts = \App\Models\CallcenterShift::where('callcenter_id', $callcenterId)
            ->whereBetween('started_at', [$from, $to])
            ->get();
            
        $totalWorkSeconds = 0;
        foreach ($shifts as $shift) {
            $start = $shift->started_at;
            if (!$start) continue;

            $end = $shift->ended_at;
            if (!$end) {
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

        $totalWorkDays = \App\Models\CallcenterShift::where('callcenter_id', $callcenterId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->distinct('date')
            ->count('date');

        // ── 5. Datatable (Paginated, Eager Loaded)
        $orders = Order::with(['client:id,name', 'callcenter:id,name'])
            ->where('callcenter_id', $callcenterId)
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->paginate(15);

        return response()->json([
            'kpis' => [
                'total_orders'        => (int) ($kpis->total_orders ?? 0),
                'total_received'      => (int) ($kpis->total_received ?? 0),
                'cancelled'           => (int) ($kpis->cancelled ?? 0),
                'pending'             => (int) ($kpis->total_pending ?? 0),
                'total_fees'          => number_format((float) ($kpis->total_fees ?? 0), 2),
                'total_discounts'     => number_format((float) ($kpis->total_discounts ?? 0), 2),
                'total_delivered_revenue' => number_format((float) $totalDeliveredRevenue, 2),
                'debtor'              => number_format((float) $totalDebit, 2),
                'creditor'            => number_format((float) $totalCredit, 2),
                'period_safe_balance' => number_format((float) $periodSafeBalance, 2),
                'raw_period_safe_balance' => $periodSafeBalance, // Raw for JS coloring logic
                'total_work_hours'    => $formattedWorkHours,
                'total_work_days'     => $totalWorkDays,
            ],
            'orders' => $orders,
            'agent_name' => User::find($callcenterId)->name
        ]);
    }
}
