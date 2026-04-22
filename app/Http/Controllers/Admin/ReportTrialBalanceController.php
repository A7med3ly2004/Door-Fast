<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;
use App\Models\TreasuryTransaction;
use App\Models\CallcenterSettlement;

class ReportTrialBalanceController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'title' => 'ميزان المراجعة',
                'html' => view('admin.report-trial-balance.partials.content')->render(),
            ]);
        }
        return view('admin.report-trial-balance.index');
    }

    public function data(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $isAlways = empty($request->from) && empty($request->to);
        
        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : null;
        $to = $request->to ? Carbon::parse($request->to)->endOfDay() : null;

        // 1. MAIN SAFE (الخزينة الرئيسية)
        $safeQuery = TreasuryTransaction::query();
        if (!$isAlways) $safeQuery->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);
        
        $safeRows = $safeQuery->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');
        
        $mainSafe = ($safeRows['income'] ?? 0) + ($safeRows['settlement'] ?? 0)
                  - ($safeRows['expense'] ?? 0) - ($safeRows['dain'] ?? 0) - ($safeRows['discount'] ?? 0);

        $totalExpenses = $safeRows['expense'] ?? 0;

        // 2. CALLCENTER ROWS (كول سنتر)
        $ccAgents = User::callcenters()->get(['id', 'name', 'code']);
        
        $ordersQuery = Order::where('status', 'delivered')->whereNotNull('callcenter_id');
        if (!$isAlways) $ordersQuery->whereBetween('created_at', [$from, $to]);
        $deliveredTotals = $ordersQuery->selectRaw('callcenter_id, SUM(total) as sum_total')->groupBy('callcenter_id')->pluck('sum_total', 'callcenter_id');
            
        $settledQuery = CallcenterSettlement::query();
        if (!$isAlways) $settledQuery->whereBetween('settled_at', [$from, $to]);
        $settledTotals = $settledQuery->selectRaw('callcenter_id, SUM(amount) as sum_amount')->groupBy('callcenter_id')->pluck('sum_amount', 'callcenter_id');
            
        $dainQuery = TreasuryTransaction::where('type', 'dain')->whereNotNull('source_id');
        if (!$isAlways) $dainQuery->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);
        $dainTotals = $dainQuery->selectRaw('source_id, SUM(amount) as sum_amount')->groupBy('source_id')->pluck('sum_amount', 'source_id');

        $ccRows = $ccAgents->map(function ($cc) use ($deliveredTotals, $settledTotals, $dainTotals) {
            $deliveredTotal = $deliveredTotals[$cc->id] ?? 0;
            $settled = $settledTotals[$cc->id] ?? 0;
            $dain = $dainTotals[$cc->id] ?? 0;
            
            $balance = $deliveredTotal - $settled - $dain;
            
            return [
                'id' => $cc->id,
                'name' => $cc->name,
                'code' => $cc->code,
                'balance' => round($balance, 2),
            ];
        });

        // 3. DELIVERY ROWS (مناديب التوصيل)
        $deliveryAgents = User::with('wallet')->whereIn('role', ['delivery', 'reserve_delivery'])->get(['id', 'name', 'code', 'unsettled_value', 'unsettled_fees']);
        
        $deliveryWalletIds = $deliveryAgents->pluck('wallet.id')->filter();
        
        $deliveryPeriodNet = [];
        if (!$isAlways && $deliveryWalletIds->isNotEmpty()) {
            $deliveryPeriodNet = \App\Models\WalletTransaction::whereIn('wallet_id', $deliveryWalletIds)
                ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw("wallet_id, SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END) as net")
                ->groupBy('wallet_id')
                ->pluck('net', 'wallet_id');
        }

        $deliveryRows = $deliveryAgents->map(function ($d) use ($isAlways, $deliveryPeriodNet) {
            if ($isAlways) {
                if ($d->wallet) {
                    $balance = $d->wallet->balance;
                } else {
                    $balance = ($d->unsettled_value ?? 0) + ($d->unsettled_fees ?? 0);
                }
            } else {
                $walletId = $d->wallet ? $d->wallet->id : 0;
                $balance = $deliveryPeriodNet[$walletId] ?? 0;
            }

            return [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'balance' => round($balance, 2),
            ];
        });

        // 4. TOTAL DISCOUNTS (إجمالي الخصومات)
        $discountsQuery = Order::where('status', 'delivered');
        if (!$isAlways) $discountsQuery->whereBetween('created_at', [$from, $to]);
        $totalDiscounts = $discountsQuery->sum('discount');

        return response()->json([
            'main_safe' => round($mainSafe, 2),
            'total_expenses' => round($totalExpenses, 2),
            'callcenter_rows' => $ccRows->values(),
            'delivery_rows' => $deliveryRows->values(),
            'total_discounts' => round($totalDiscounts, 2),
            'period' => [
                'from' => $isAlways ? '' : $from->format('Y-m-d'),
                'to' => $isAlways ? '' : $to->format('Y-m-d'),
            ]
        ]);
    }
}
