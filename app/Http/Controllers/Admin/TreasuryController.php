<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\TreasuryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // Shared filter validation rules (reused across index/stats/data)
    // ──────────────────────────────────────────────────────────────

    private function filterRules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'type' => ['nullable', 'in:income,expense,settlement,dain,discount,pay_to_user,receive_from_user'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // index — Full page load + SPA navigation
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /admin/treasury
     *
     * On a direct URL visit   → returns the full page (extends admin layout).
     * On SPA navigation       → returns JSON { html, title, csrf_token }.
     *
     * Initial data is passed server-side to eliminate the blank-screen flash
     * on first load, exactly like the Admin Dashboard pattern.
     * The JS on the page then starts polling stats/data independently.
     */
    public function index(Request $request): View|JsonResponse
    {
        // Default filter values for the initial page load
        // (no date range, no type filter = show everything)
        $from = $request->input('from');
        $to = $request->input('to');
        $type = $request->input('type');

        // Server-side initial data — avoids blank KPI cards on first render
        $initialStats = TreasuryTransaction::calculateKpis($from, $to);

        // First page of ledger data baked in for instant table render
        $initialTransactions = $this->buildLedgerQuery($from, $to, $type)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $data = [
            'initialStats' => $initialStats,
            'initialTransactions' => $initialTransactions,
            'filters' => compact('from', 'to', 'type'),
            // Lists for the "Dain" modal
            'callcenters' => \App\Models\User::callcenters()->active()->get(['id', 'name']),
            'deliveries'  => \App\Models\User::whereIn('role', ['delivery', 'reserve_delivery'])->active()->get(['id', 'name']),
            'admins'      => \App\Models\User::where('role', 'admin')->where('is_active', true)->where('id', '!=', auth()->id())->get(['id', 'name']),
        ];

        // ── SPA navigation request ────────────────────────────────
        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.treasury.partials.content', $data)->render(),
                'title' => 'الخزينة',
                'csrf_token' => csrf_token(),
            ]);
        }

        // ── Direct URL request — full page ────────────────────────
        return view('admin.treasury.index', $data);
    }

    // ──────────────────────────────────────────────────────────────
    // stats — KPI cards (polled every 30s by the page JS)
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /admin/treasury/stats
     *
     * Returns the three KPI values filtered by the active date range.
     * The type filter does NOT affect KPI calculations — balance always
     * reflects the full financial picture for the selected period.
     *
     * Response shape:
     * {
     *   "total_income":  "1,250.00",
     *   "total_expense": "430.00",
     *   "balance":       "820.00"
     * }
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $stats = TreasuryTransaction::calculateKpis(
            $request->input('from'),
            $request->input('to')
        );

        return response()->json($stats);
    }

    // ──────────────────────────────────────────────────────────────
    // data — Paginated ledger table (polled every 30s by the page JS)
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /admin/treasury/data
     *
     * Returns a paginated JSON array of ledger rows for the table.
     * All three filters (from, to, type) are applied here.
     *
     * Response shape:
     * {
     *   "data": [
     *     {
     *       "id": 12,
     *       "transaction_date": "2024-01-15",
     *       "type": "income",
     *       "type_label": "إيراد",
     *       "type_badge_class": "success",
     *       "amount": "500.00",
     *       "by_whom": "أحمد محمد",
     *       "note": "دفعة من العميل",
     *       "is_settlement": false,
     *       "source_id": null
     *     },
     *     ...
     *   ],
     *   "current_page": 1,
     *   "last_page": 3,
     *   "total": 42,
     *   "per_page": 15
     * }
     */
    public function data(Request $request): JsonResponse
    {
        $request->validate($this->filterRules());

        $from = $request->input('from');
        $to = $request->input('to');
        $type = $request->input('type');

        $paginator = $this->buildLedgerQuery($from, $to, $type)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Transform each row into the shape the JS table renderer expects.
        // Using a manual map keeps this lean — no API resources needed for
        // an internal admin panel with a fixed shape.
        $rows = $paginator->getCollection()->map(function (TreasuryTransaction $tx) {
            return [
                'id' => $tx->id,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'type' => $tx->type,
                'type_label' => $tx->type_label,        // accessor on model
                'type_badge_class' => $tx->type_badge_class,  // accessor on model
                'amount' => number_format((float) $tx->amount, 2),
                'by_whom' => $tx->recordedBy?->name ?? '—',
                'note' => $tx->note ?? '—',
                'is_settlement' => $tx->source_type === 'settlement',
                'source_id' => $tx->source_id,
            ];
        });

        return response()->json([
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // show — Single transaction detail (for the "View Details" modal)
    // ──────────────────────────────────────────────────────────────

    /**
     * GET /admin/treasury/{transaction}
     *
     * Returns full detail for one transaction.
     * For settlements, eager-loads the parent callcenter_settlement
     * and the CC agent name so the modal can show the origin.
     */
    public function show(TreasuryTransaction $transaction): JsonResponse
    {
        // Only load the settlement relation when it actually exists
        if ($transaction->source_type === 'settlement' && $transaction->source_id) {
            $transaction->load([
                'settlement.callcenter:id,name,phone',
                'settlement.settledBy:id,name',
            ]);
        }

        $transaction->load('recordedBy:id,name');

        $detail = [
            'id' => $transaction->id,
            'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
            'type' => $transaction->type,
            'type_label' => $transaction->type_label,
            'type_badge_class' => $transaction->type_badge_class,
            'amount' => number_format((float) $transaction->amount, 2),
            'by_whom' => $transaction->by_whom,
            'note' => $transaction->note ?? '—',
            'recorded_by' => $transaction->recordedBy?->name ?? '—',
            'created_at' => $transaction->created_at->format('d/m/Y H:i'),
            'is_settlement' => $transaction->source_type === 'settlement',
            // Settlement origin detail (null for manual entries)
            'settlement' => $transaction->source_type === 'settlement' && $transaction->settlement
                ? [
                    'agent_name' => $transaction->settlement->callcenter?->name,
                    'agent_phone' => $transaction->settlement->callcenter?->phone,
                    'settled_by' => $transaction->settlement->settledBy?->name,
                    'settled_at' => $transaction->settlement->settled_at->format('d/m/Y H:i'),
                    'note' => $transaction->settlement->note ?? '—',
                ]
                : null,
        ];

        return response()->json($detail);
    }

    public function addIncome(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'by_whom' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $transaction = TreasuryTransaction::createManual(
            type: 'income',
            byWhom: $validated['by_whom'],
            amount: (float) $validated['amount'],
            note: $validated['note'] ?? null,
            recordedBy: auth()->id(),
            transactionDate: $validated['date'] ?? null,
        );

        ActivityLog::log(
            event: 'treasury.income',
            description: 'تم إضافة وارد في الخزينة — ' . $validated['by_whom'],
            subjectType: 'treasury',
            subjectId: $transaction->id,
            subjectLabel: number_format((float) $validated['amount'], 2) . ' ج',
            properties: ['by_whom' => $validated['by_whom'], 'amount' => $validated['amount'], 'note' => $validated['note'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الإيراد بنجاح.',
            'transaction' => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
                'type' => $transaction->type,
                'type_label' => $transaction->type_label,
                'type_badge_class' => $transaction->type_badge_class,
                'amount' => number_format((float) $transaction->amount, 2),
                'by_whom' => $transaction->by_whom,
                'note' => $transaction->note ?? '—',
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // addExpense — POST /admin/treasury/expense
    // ──────────────────────────────────────────────────────────────

    /**
     * Store a manual expense entry in the treasury ledger.
     *
     * Identical validation and shape to addIncome().
     * Only the `type` argument to createManual() differs.
     */
    public function addExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'by_whom' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $transaction = TreasuryTransaction::createManual(
            type: 'expense',
            byWhom: $validated['by_whom'],
            amount: (float) $validated['amount'],
            note: $validated['note'] ?? null,
            recordedBy: auth()->id(),
            transactionDate: $validated['date'] ?? null,
        );

        ActivityLog::log(
            event: 'treasury.expense',
            description: 'تم إضافة مصروف في الخزينة — ' . $validated['by_whom'],
            subjectType: 'treasury',
            subjectId: $transaction->id,
            subjectLabel: number_format((float) $validated['amount'], 2) . ' ج',
            properties: ['by_whom' => $validated['by_whom'], 'amount' => $validated['amount'], 'note' => $validated['note'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المصروف بنجاح.',
            'transaction' => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
                'type' => $transaction->type,
                'type_label' => $transaction->type_label,
                'type_badge_class' => $transaction->type_badge_class,
                'amount' => number_format((float) $transaction->amount, 2),
                'by_whom' => $transaction->by_whom,
                'note' => $transaction->note ?? '—',
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // addDain — POST /admin/treasury/dain
    // ──────────────────────────────────────────────────────────────

    /**
     * Store a manual "Dain" entry in the treasury ledger.
     * Treated as an expense based on user feedback.
     */
    public function addDain(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'callcenter_id' => ['nullable', 'exists:users,id'],
            'delivery_id'   => ['nullable', 'exists:users,id'],
            'amount'        => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note'          => ['nullable', 'string', 'max:500'],
            'date'          => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        // Ensure exactly one is selected
        if (($validated['callcenter_id'] && $validated['delivery_id']) || (!$validated['callcenter_id'] && !$validated['delivery_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'يجب اختيار كول سينتر أو مندوب فقط.',
                'errors' => [
                    'callcenter_id' => ['يجب اختيار واحد فقط.'],
                    'delivery_id'   => ['يجب اختيار واحد فقط.'],
                ]
            ], 422);
        }

        $userId = $validated['callcenter_id'] ?? $validated['delivery_id'];
        $user = \App\Models\User::find($userId);

        // Map to TreasuryTransaction
        // type = 'dain' (which model now treats as subtraction from balance)
        $transaction = \App\Models\TreasuryTransaction::create([
            'type'             => 'dain',
            'source_type'      => 'manual',
            'source_id'        => $user->id,
            'amount'           => (float) $validated['amount'],
            'by_whom'          => $user->name,
            'note'             => $validated['note'] ?? null,
            'recorded_by'      => auth()->id(),
            'transaction_date' => $validated['date'] ?? now()->toDateString(),
        ]);

        \App\Models\ActivityLog::log(
            event: 'treasury.dain',
            description: 'تم إضافة صرف مديونية في الخزينة — ' . $user->name,
            subjectType: 'treasury',
            subjectId: $transaction->id,
            subjectLabel: number_format((float) $validated['amount'], 2) . ' ج',
            properties: [
                'user_id' => $user->id,
                'role' => $user->role,
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العملية بنجاح.',
            'transaction' => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
                'type' => $transaction->type,
                'type_label' => $transaction->type_label,
                'type_badge_class' => $transaction->type_badge_class,
                'amount' => number_format((float) $transaction->amount, 2),
                'by_whom' => $transaction->by_whom,
                'note' => $transaction->note ?? '—',
            ],
        ], 201);
    }

    /**
     * Store a manual "Discount" entry in the treasury ledger.
     */
    public function addDiscount(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'callcenter_id' => ['nullable', 'exists:users,id'],
            'delivery_id'   => ['nullable', 'exists:users,id'],
            'amount'        => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note'          => ['nullable', 'string', 'max:500'],
            'date'          => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        // Ensure exactly one is selected
        if (($validated['callcenter_id'] && $validated['delivery_id']) || (!$validated['callcenter_id'] && !$validated['delivery_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'يجب اختيار كول سينتر أو مندوب فقط.',
                'errors' => [
                    'callcenter_id' => ['يجب اختيار واحد فقط.'],
                    'delivery_id'   => ['يجب اختيار واحد فقط.'],
                ]
            ], 422);
        }

        $userId = $validated['callcenter_id'] ?? $validated['delivery_id'];
        $user = \App\Models\User::find($userId);

        $transaction = \App\Models\TreasuryTransaction::create([
            'type'             => 'discount',
            'source_type'      => 'manual',
            'source_id'        => $user->id,
            'amount'           => (float) $validated['amount'],
            'by_whom'          => $user->name,
            'note'             => $validated['note'] ?? null,
            'recorded_by'      => auth()->id(),
            'transaction_date' => $validated['date'] ?? now()->toDateString(),
        ]);

        \App\Models\ActivityLog::log(
            event: 'treasury.discount',
            description: 'تم إضافة خصم في الخزينة — ' . $user->name,
            subjectType: 'treasury',
            subjectId: $transaction->id,
            subjectLabel: number_format((float) $validated['amount'], 2) . ' ج',
            properties: [
                'user_id' => $user->id,
                'role' => $user->role,
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الخصم بنجاح.',
            'transaction' => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
                'type' => $transaction->type,
                'type_label' => $transaction->type_label,
                'type_badge_class' => $transaction->type_badge_class,
                'amount' => number_format((float) $transaction->amount, 2),
                'by_whom' => $transaction->by_whom,
                'note' => $transaction->note ?? '—',
            ],
        ], 201);
    }

    /**
     * Arabic validation error messages shared by addIncome / addExpense.
     * Laravel's default messages are in English — this gives Arabic users
     * meaningful inline feedback without any lang file setup.
     */
    private function validationMessages(): array
    {
        return [
            'by_whom.required' => 'حقل "بواسطة" مطلوب.',
            'by_whom.max' => 'يجب ألا يتجاوز حقل "بواسطة" 100 حرف.',
            'amount.required' => 'حقل "المبلغ" مطلوب.',
            'amount.numeric' => 'يجب أن يكون المبلغ رقمًا.',
            'amount.gt' => 'يجب أن يكون المبلغ أكبر من صفر.',
            'amount.max' => 'المبلغ كبير جدًا.',
            'note.max' => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
            'date.date_format' => 'صيغة التاريخ غير صحيحة.',
            'date.before_or_equal' => 'لا يمكن إدخال تاريخ مستقبلي.',
            'user_id.required' => 'يجب اختيار الموظف.',
            'user_id.exists' => 'الموظف المختار غير موجود.',
            'description.max' => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // payToUser — POST /admin/treasury/pay-to-user
    // دفع نقدي من خزينة الأدمن إلى خزينة موظف
    // ──────────────────────────────────────────────────────────────

    public function payToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'     => ['required', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
            'date'        => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $admin = auth()->user();
        $targetUser = \App\Models\User::findOrFail($validated['user_id']);
        $walletService = app(\App\Services\WalletService::class);
        $date = $validated['date'] ?? now()->toDateString();
        $description = $validated['description'] ?? ('دفع نقدي إلى ' . $targetUser->name);

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $admin, $targetUser, $walletService, $validated, $date, $description
        ) {
            $adminWallet  = $admin->getOrCreateWallet();
            $targetWallet = $targetUser->getOrCreateWallet();

            // خصم من خزينة الأدمن
            $walletService->debit(
                wallet:          $adminWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_paid',
                description:     'دفع نقدي إلى ' . $targetUser->name . ($description !== ('دفع نقدي إلى ' . $targetUser->name) ? ' — ' . $description : ''),
                createdBy:       $admin->id,
                relatedWalletId: $targetWallet->id,
                date:            $date
            );

            // إضافة لخزينة الموظف
            $walletService->credit(
                wallet:          $targetWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_received',
                description:     'استلام نقدي من الإدارة' . ($description ? ' — ' . $description : ''),
                createdBy:       $admin->id,
                relatedWalletId: $adminWallet->id,
                date:            $date
            );
        });

        ActivityLog::log(
            event: 'wallet.pay_to_user',
            description: 'دفع نقدي إلى ' . $targetUser->name . ' — ' . number_format((float) $validated['amount'], 2) . ' ج',
            subjectType: 'wallet',
            subjectId: $targetUser->id,
            subjectLabel: $targetUser->name,
            properties: [
                'user_id' => $targetUser->id,
                'amount'  => $validated['amount'],
                'note'    => $validated['description'] ?? null,
            ]
        );

        // ── سجل في الخزينة كمعاملة دفع لموظف ──────────────────────────
        TreasuryTransaction::create([
            'type'             => 'pay_to_user',
            'source_type'      => 'manual',
            'source_id'        => $targetUser->id,
            'amount'           => (float) $validated['amount'],
            'by_whom'          => $targetUser->name,
            'note'             => $validated['description'] ?? ('دفع نقدي إلى ' . $targetUser->name),
            'recorded_by'      => auth()->id(),
            'transaction_date' => $date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم دفع ' . number_format((float) $validated['amount'], 2) . ' ج إلى ' . $targetUser->name . ' بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // receiveFromUser — POST /admin/treasury/receive-from-user
    // استلام نقدي من خزينة موظف إلى خزينة الأدمن
    // ──────────────────────────────────────────────────────────────

    public function receiveFromUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'     => ['required', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
            'date'        => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $admin = auth()->user();
        $targetUser = \App\Models\User::findOrFail($validated['user_id']);
        $walletService = app(\App\Services\WalletService::class);
        $date = $validated['date'] ?? now()->toDateString();
        $description = $validated['description'] ?? ('استلام نقدي من ' . $targetUser->name);

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $admin, $targetUser, $walletService, $validated, $date, $description
        ) {
            $adminWallet  = $admin->getOrCreateWallet();
            $targetWallet = $targetUser->getOrCreateWallet();

            // إضافة لخزينة الأدمن
            $walletService->credit(
                wallet:          $adminWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_received',
                description:     'استلام نقدي من ' . $targetUser->name . ($description !== ('استلام نقدي من ' . $targetUser->name) ? ' — ' . $description : ''),
                createdBy:       $admin->id,
                relatedWalletId: $targetWallet->id,
                date:            $date
            );

            // خصم من خزينة الموظف
            $walletService->debit(
                wallet:          $targetWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_paid',
                description:     'دفع نقدي للإدارة' . ($description ? ' — ' . $description : ''),
                createdBy:       $admin->id,
                relatedWalletId: $adminWallet->id,
                date:            $date
            );
        });

        ActivityLog::log(
            event: 'wallet.receive_from_user',
            description: 'استلام نقدي من ' . $targetUser->name . ' — ' . number_format((float) $validated['amount'], 2) . ' ج',
            subjectType: 'wallet',
            subjectId: $targetUser->id,
            subjectLabel: $targetUser->name,
            properties: [
                'user_id' => $targetUser->id,
                'amount'  => $validated['amount'],
                'note'    => $validated['description'] ?? null,
            ]
        );

        // ── سجل في الخزينة كمعاملة استلام من موظف ─────────────────────
        TreasuryTransaction::create([
            'type'             => 'receive_from_user',
            'source_type'      => 'manual',
            'source_id'        => $targetUser->id,
            'amount'           => (float) $validated['amount'],
            'by_whom'          => $targetUser->name,
            'note'             => $validated['description'] ?? ('استلام نقدي من ' . $targetUser->name),
            'recorded_by'      => auth()->id(),
            'transaction_date' => $date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام ' . number_format((float) $validated['amount'], 2) . ' ج من ' . $targetUser->name . ' بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // update — PATCH /admin/treasury/{transaction}
    // ──────────────────────────────────────────────────────────────

    public function update(Request $request, TreasuryTransaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'by_whom' => ['required', 'string', 'max:100'],
            'amount'  => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note'    => ['nullable', 'string', 'max:500'],
            'date'    => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $transaction->update([
            'by_whom'          => $validated['by_whom'],
            'amount'           => $validated['amount'],
            'note'             => $validated['note'] ?? $transaction->note,
            'transaction_date' => $validated['date'] ?? $transaction->transaction_date,
        ]);

        ActivityLog::log(
            event: 'treasury.updated',
            description: 'تم تعديل معاملة مالية',
            subjectType: 'treasury',
            subjectId: $transaction->id,
            subjectLabel: number_format((float) $transaction->amount, 2) . ' ج'
        );

        return response()->json(['success' => true, 'message' => 'تم التعديل بنجاح']);
    }

    // ──────────────────────────────────────────────────────────────
    // exportPdf — GET /admin/treasury/{transaction}/pdf
    // ──────────────────────────────────────────────────────────────

    public function exportPdf(TreasuryTransaction $transaction)
    {
        $transaction->load('recordedBy:id,name');
        $html = view('admin.pdf.treasury-transaction', compact('transaction'))->render();

        $Arabic = new \ArPHP\I18N\Arabic();
        $p = $Arabic->arIdentify($html);
        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $html = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->download('transaction-' . $transaction->id . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build the base ledger query with all filters applied.
     * Reused by index() (server-side initial data) and data() (AJAX polls).
     *
     * @param string|null $from  Y-m-d or null
     * @param string|null $to    Y-m-d or null
     * @param string|null $type  'income'|'expense'|'settlement'|null
     */
    private function buildLedgerQuery(?string $from, ?string $to, ?string $type)
    {
        return TreasuryTransaction::query()
            ->withinDateRange($from, $to)  // scope on model
            ->ofType($type)                // scope on model
            ->with('recordedBy:id,name')
            ->select([
                'id',
                'type',
                'source_type',
                'source_id',
                'amount',
                'by_whom',
                'note',
                'transaction_date',
                'recorded_by',
                'created_at',
            ]);
    }
}

