<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // index — Full page + SPA navigation
    // ──────────────────────────────────────────────────────────────

    public function index(Request $request): View|JsonResponse
    {
        $data = [];

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.general-ledger.partials.content', $data)->render(),
                'title'      => 'كشف حساب عام',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.general-ledger.index', $data);
    }

    // ──────────────────────────────────────────────────────────────
    // data — JSON list of all users with wallet summaries
    // ──────────────────────────────────────────────────────────────

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $request->input('from');
        $to   = $request->input('to');

        $roleLabels = [
            'admin'            => 'أدمن',
            'callcenter'       => 'كول سينتر',
            'delivery'         => 'مندوب',
            'reserve_delivery' => 'مندوب احتياطي',
        ];

        // Get all users with wallets
        $users = User::with('wallet')
            ->whereIn('role', ['admin', 'callcenter', 'delivery', 'reserve_delivery'])
            ->orderByRaw("FIELD(role, 'admin', 'callcenter', 'delivery', 'reserve_delivery')")
            ->orderBy('name')
            ->get();

        // ── Treasury row (special — not a user wallet) ──────────────
        $treasuryKpis = TreasuryTransaction::calculateKpis($from, $to);
        $treasuryBalance = str_replace(',', '', $treasuryKpis['balance']);

        // Calculate debit/credit totals for treasury
        $treasuryQuery = TreasuryTransaction::query();
        if ($from) $treasuryQuery->whereDate('transaction_date', '>=', $from);
        if ($to) $treasuryQuery->whereDate('transaction_date', '<=', $to);

        $treasuryTotals = (clone $treasuryQuery)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type IN ('income','settlement','receive_from_user') THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN type IN ('expense','dain','discount','pay_to_user') THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        $treasuryRow = [
            'user_id'      => 'treasury',
            'name'         => 'الخزينة الرئيسية',
            'role'         => 'treasury',
            'role_label'   => 'خزينة',
            'total_debit'  => number_format((float) $treasuryTotals->total_debit, 2),
            'total_credit' => number_format((float) $treasuryTotals->total_credit, 2),
            'balance'      => number_format((float) $treasuryBalance, 2),
        ];

        // ── User rows ──────────────────────────────────────────────
        $rows = $users->map(function (User $user) use ($from, $to, $roleLabels) {
            $wallet = $user->wallet;

            if (!$wallet) {
                return [
                    'user_id'      => $user->id,
                    'name'         => $user->name,
                    'role'         => $user->role,
                    'role_label'   => $roleLabels[$user->role] ?? $user->role,
                    'total_debit'  => '0.00',
                    'total_credit' => '0.00',
                    'balance'      => '0.00',
                ];
            }

            $query = WalletTransaction::where('wallet_id', $wallet->id);

            if ($from) {
                $query->where('transaction_date', '>=', $from);
            }
            if ($to) {
                $query->where('transaction_date', '<=', $to);
            }

            $totals = (clone $query)
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                    COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
                ")
                ->first();

            return [
                'user_id'      => $user->id,
                'name'         => $user->name,
                'role'         => $user->role,
                'role_label'   => $roleLabels[$user->role] ?? $user->role,
                'total_debit'  => number_format((float) $totals->total_debit, 2),
                'total_credit' => number_format((float) $totals->total_credit, 2),
                'balance'      => number_format((float) $wallet->balance, 2),
            ];
        });

        // Prepend treasury row
        $allRows = collect([$treasuryRow])->merge($rows);

        return response()->json(['data' => $allRows->values()]);
    }

    // ──────────────────────────────────────────────────────────────
    // treasuryStatement — Detailed statement for treasury
    // ──────────────────────────────────────────────────────────────

    public function treasuryStatement(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $request->input('from');
        $to   = $request->input('to');

        $query = TreasuryTransaction::query();
        if ($from) $query->whereDate('transaction_date', '>=', $from);
        if ($to) $query->whereDate('transaction_date', '<=', $to);

        // Totals
        $totals = (clone $query)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type IN ('income','settlement','receive_from_user') THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN type IN ('expense','dain','discount','pay_to_user') THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        $kpis = TreasuryTransaction::calculateKpis($from, $to);
        $currentBalance = str_replace(',', '', $kpis['balance']);

        // All transactions
        $typeLabels = [
            'income' => 'إيراد',
            'expense' => 'مصروف',
            'settlement' => 'تسوية',
            'dain' => 'صرف مديونية',
            'discount' => 'خصم',
            'pay_to_user' => 'دفع لموظف',
            'receive_from_user' => 'استلام من موظف',
        ];

        $transactions = (clone $query)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (TreasuryTransaction $tx) use ($typeLabels) {
                $isDebit = in_array($tx->type, ['income', 'settlement', 'receive_from_user']);
                return [
                    'id'               => $tx->id,
                    'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                    'description'      => ($typeLabels[$tx->type] ?? $tx->type) . ' — ' . ($tx->by_whom ?? '') . ($tx->note ? ' | ' . $tx->note : ''),
                    'type_label'       => $typeLabels[$tx->type] ?? $tx->type,
                    'debit'            => $isDebit ? number_format((float) $tx->amount, 2) : '',
                    'credit'           => !$isDebit ? number_format((float) $tx->amount, 2) : '',
                    'balance_after'    => '—',
                ];
            });

        // Calculate running balance
        $runningBalance = 0;
        $txArray = $transactions->toArray();
        foreach ($txArray as &$tx) {
            if ($tx['debit']) {
                $runningBalance += (float) str_replace(',', '', $tx['debit']);
            }
            if ($tx['credit']) {
                $runningBalance -= (float) str_replace(',', '', $tx['credit']);
            }
            $tx['balance_after'] = number_format($runningBalance, 2);
        }

        $periodBalance = (float) $totals->total_debit - (float) $totals->total_credit;

        return response()->json([
            'user' => [
                'id'         => 'treasury',
                'name'       => 'الخزينة الرئيسية',
                'role'       => 'treasury',
                'role_label' => 'خزينة',
            ],
            'summary' => [
                'total_debit'    => number_format((float) $totals->total_debit, 2),
                'total_credit'   => number_format((float) $totals->total_credit, 2),
                'period_balance' => number_format($periodBalance, 2),
                'current_balance' => number_format((float) $currentBalance, 2),
            ],
            'transactions' => $txArray,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // userStatement — Detailed statement for one user
    // ──────────────────────────────────────────────────────────────

    public function userStatement(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $request->input('from');
        $to   = $request->input('to');

        $user = User::findOrFail($userId);
        $wallet = $user->getOrCreateWallet();

        $roleLabels = [
            'admin'            => 'أدمن',
            'callcenter'       => 'كول سينتر',
            'delivery'         => 'مندوب',
            'reserve_delivery' => 'مندوب احتياطي',
        ];

        $query = WalletTransaction::where('wallet_id', $wallet->id);

        if ($from) {
            $query->where('transaction_date', '>=', $from);
        }
        if ($to) {
            $query->where('transaction_date', '<=', $to);
        }

        // Totals for the period
        $totals = (clone $query)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        // All transactions ordered by date
        $transactions = (clone $query)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (WalletTransaction $tx) {
                return [
                    'id'               => $tx->id,
                    'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                    'description'      => $tx->description ?? '—',
                    'type_label'       => $tx->type_label,
                    'debit'            => $tx->direction === 'debit' ? number_format((float) $tx->amount, 2) : '',
                    'credit'           => $tx->direction === 'credit' ? number_format((float) $tx->amount, 2) : '',
                    'balance_after'    => number_format((float) $tx->balance_after, 2),
                ];
            });

        $periodBalance = (float) $totals->total_debit - (float) $totals->total_credit;

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'role'       => $user->role,
                'role_label' => $roleLabels[$user->role] ?? $user->role,
            ],
            'summary' => [
                'total_debit'    => number_format((float) $totals->total_debit, 2),
                'total_credit'   => number_format((float) $totals->total_credit, 2),
                'period_balance' => number_format($periodBalance, 2),
                'current_balance' => number_format((float) $wallet->balance, 2),
            ],
            'transactions' => $transactions,
        ]);
    }
}
