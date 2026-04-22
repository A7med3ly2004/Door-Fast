<?php

namespace App\Http\Controllers\ReserveDelivery;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('reserve_delivery.wallet.partials.content')->render(),
                'title'      => 'كشف حسابي',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('reserve_delivery.wallet.index');
    }

    public function statement(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $user   = auth()->user();
        $wallet = $user->getOrCreateWallet();
        $from   = $request->input('from');
        $to     = $request->input('to');

        $query = WalletTransaction::where('wallet_id', $wallet->id);

        if ($from) $query->where('transaction_date', '>=', $from);
        if ($to)   $query->where('transaction_date', '<=', $to);

        $totals = (clone $query)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        $transactions = (clone $query)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn(WalletTransaction $tx) => [
                'id'               => $tx->id,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'description'      => $tx->description ?? '—',
                'debit'            => $tx->direction === 'debit' ? number_format((float) $tx->amount, 2) : '',
                'credit'           => $tx->direction === 'credit' ? number_format((float) $tx->amount, 2) : '',
                'balance_after'    => number_format((float) $tx->balance_after, 2),
            ]);

        return response()->json([
            'summary' => [
                'total_debit'     => number_format((float) $totals->total_debit, 2),
                'total_credit'    => number_format((float) $totals->total_credit, 2),
                'current_balance' => number_format((float) $wallet->balance, 2),
            ],
            'transactions' => $transactions,
        ]);
    }
}
