<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WalletController extends Controller
{
    /**
     * GET /api/delivery/wallet/statement
     * Returns the wallet balance and a list of transactions (ledger).
     */
    public function statement(Request $request)
    {
        $delivery = $request->user();
        $wallet = $delivery->getOrCreateWallet();

        // Default to last 30 days if no dates provided
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('transaction_date')
            ->orderBy('id', 'asc');

        $transactions = $query->paginate(20);

        // Summary totals for the selected period
        $totals = WalletTransaction::where('wallet_id', $wallet->id)
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'current_balance' => (float) $wallet->balance,
                'total_debit' => (float) ($totals->total_debit ?? 0),
                'total_credit' => (float) ($totals->total_credit ?? 0),
                'transactions' => $transactions
            ]
        ]);
    }
}
