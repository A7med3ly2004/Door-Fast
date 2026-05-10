<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TreasuryTransaction;
use App\Services\TreasuryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TreasuryController
 *
 * المسؤوليات:
 *  1. استقبال الـ Request
 *  2. Validation (هنا فقط)
 *  3. استدعاء TreasuryService بالـ validated data
 *  4. بناء الـ Response وإرجاعها
 *
 * لا يوجد أي business logic هنا.
 */
class TreasuryController extends Controller
{
    public function __construct(private TreasuryService $treasury) {}

    // ──────────────────────────────────────────────────────────────
    // Shared filter validation rules (reused across index/stats/data)
    // ──────────────────────────────────────────────────────────────

    private function filterRules(): array
    {
        return [
            'from'    => ['nullable', 'date_format:Y-m-d'],
            'to'      => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'type'    => ['nullable', 'in:income,settlement,dain,pay_to_user,receive_from_user'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Arabic validation error messages shared by all write methods.
     */
    private function validationMessages(): array
    {
        return [
            'by_whom.required'      => 'حقل "بواسطة" مطلوب.',
            'by_whom.max'           => 'يجب ألا يتجاوز حقل "بواسطة" 100 حرف.',
            'amount.required'       => 'حقل "المبلغ" مطلوب.',
            'amount.numeric'        => 'يجب أن يكون المبلغ رقمًا.',
            'amount.gt'             => 'يجب أن يكون المبلغ أكبر من صفر.',
            'amount.max'            => 'المبلغ كبير جدًا.',
            'note.max'              => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
            'date.date_format'      => 'صيغة التاريخ غير صحيحة.',
            'date.before_or_equal'  => 'لا يمكن إدخال تاريخ مستقبلي.',
            'user_id.required'      => 'يجب اختيار الموظف.',
            'user_id.exists'        => 'الموظف المختار غير موجود.',
            'description.max'       => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
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
     */
    public function index(Request $request): View|JsonResponse
    {
        $from     = $request->input('from');
        $to       = $request->input('to');
        $type     = $request->input('type');
        $userId   = $request->input('user_id');

        $initialStats        = TreasuryTransaction::calculateKpis($from, $to, $userId);
        $initialTransactions = $this->buildLedgerQuery($from, $to, $type, $userId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $data = [
            'initialStats'        => $initialStats,
            'initialTransactions' => $initialTransactions,
            'filters'             => compact('from', 'to', 'type', 'userId'),
            // الخزينة تتعامل فقط مع المديرين (دفع/استلام بين المدير والمديرين الآخرين)
            'admins'              => \App\Models\User::where('role', 'admin')->where('is_active', true)->where('id', '!=', auth()->id())->with('wallet')->get(['id', 'name']),
            // callcenters/deliveries مطلوبة لـ modal صرف المديونية (dain)
            'callcenters'         => \App\Models\User::callcenters()->active()->get(['id', 'name']),
            'deliveries'          => \App\Models\User::whereIn('role', ['delivery', 'reserve_delivery'])->active()->get(['id', 'name']),
        ];

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('admin.treasury.partials.content', $data)->render(),
                'title'      => 'الخزينة',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.treasury.index', $data);
    }

    // ──────────────────────────────────────────────────────────────
    // stats — KPI cards
    // ──────────────────────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'from'    => ['nullable', 'date_format:Y-m-d'],
            'to'      => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        return response()->json(
            TreasuryTransaction::calculateKpis(
                $request->input('from'),
                $request->input('to'),
                $request->input('user_id')
            )
        );
    }

    // ──────────────────────────────────────────────────────────────
    // data — Paginated ledger table
    // ──────────────────────────────────────────────────────────────

    public function data(Request $request): JsonResponse
    {
        $request->validate($this->filterRules());

        $from    = $request->input('from');
        $to      = $request->input('to');
        $type    = $request->input('type');
        $userId  = $request->input('user_id');
        $perPage = min((int) $request->get('per_page', 15), 5000);

        $paginator = $this->buildLedgerQuery($from, $to, $type, $userId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $rows = $paginator->getCollection()->map(function (TreasuryTransaction $tx) {
            return [
                'id'               => $tx->id,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'type'             => $tx->type,
                'type_label'       => $tx->type_label,
                'type_badge_class' => $tx->type_badge_class,
                'amount'           => number_format((float) $tx->amount, 2),
                'by_whom'          => $tx->recordedBy?->name ?? '—',
                'note'             => $tx->note ?? '—',
                'is_settlement'    => $tx->source_type === 'settlement',
                'source_id'        => $tx->source_id,
            ];
        });

        return response()->json([
            'data'         => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // show — Single transaction detail
    // ──────────────────────────────────────────────────────────────

    public function show(TreasuryTransaction $transaction): JsonResponse
    {
        if ($transaction->source_type === 'settlement' && $transaction->source_id) {
            $transaction->load([
                'settlement.callcenter:id,name,phone',
                'settlement.settledBy:id,name',
            ]);
        }

        $transaction->load('recordedBy:id,name');

        return response()->json([
            'id'               => $transaction->id,
            'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
            'type'             => $transaction->type,
            'type_label'       => $transaction->type_label,
            'type_badge_class' => $transaction->type_badge_class,
            'amount'           => number_format((float) $transaction->amount, 2),
            'by_whom'          => $transaction->by_whom,
            'note'             => $transaction->note ?? '—',
            'recorded_by'      => $transaction->recordedBy?->name ?? '—',
            'created_at'       => $transaction->created_at->format('d/m/Y H:i'),
            'transaction_time' => $transaction->created_at->format('H:i'),
            'is_settlement'    => $transaction->source_type === 'settlement',
            'settlement'       => $transaction->source_type === 'settlement' && $transaction->settlement
                ? [
                    'agent_name'  => $transaction->settlement->callcenter?->name,
                    'agent_phone' => $transaction->settlement->callcenter?->phone,
                    'settled_by'  => $transaction->settlement->settledBy?->name,
                    'settled_at'  => $transaction->settlement->settled_at->format('d/m/Y H:i'),
                    'note'        => $transaction->settlement->note ?? '—',
                ]
                : null,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // addIncome — POST /admin/treasury/income
    // ──────────────────────────────────────────────────────────────

    public function addIncome(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'by_whom' => ['required', 'string', 'max:100'],
            'amount'  => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note'    => ['nullable', 'string', 'max:500'],
            'date'    => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $transaction = $this->treasury->addIncome($validated, auth()->id());

        return response()->json([
            'success'     => true,
            'message'     => 'تم إضافة الإيراد بنجاح.',
            'transaction' => $this->formatTransaction($transaction),
        ], 201);
    }


    // ──────────────────────────────────────────────────────────────
    // addDain — POST /admin/treasury/dain
    // ──────────────────────────────────────────────────────────────

    public function addDain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'callcenter_id' => ['nullable', 'exists:users,id'],
            'delivery_id'   => ['nullable', 'exists:users,id'],
            'amount'        => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note'          => ['nullable', 'string', 'max:500'],
            'date'          => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        // Ensure exactly one is selected — HTTP-layer guard
        if (($validated['callcenter_id'] && $validated['delivery_id'])
            || (!$validated['callcenter_id'] && !$validated['delivery_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'يجب اختيار كول سينتر أو مندوب فقط.',
                'errors'  => [
                    'callcenter_id' => ['يجب اختيار واحد فقط.'],
                    'delivery_id'   => ['يجب اختيار واحد فقط.'],
                ],
            ], 422);
        }

        $transaction = $this->treasury->addDain($validated, auth()->id());

        return response()->json([
            'success'     => true,
            'message'     => 'تم إضافة العملية بنجاح.',
            'transaction' => $this->formatTransaction($transaction),
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // payToUser — POST /admin/treasury/pay-to-user
    // ──────────────────────────────────────────────────────────────

    public function payToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'     => ['required', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
            'date'        => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $transaction = $this->treasury->payToUser($validated, auth()->user());
        $amount      = number_format((float) $validated['amount'], 2);
        $targetName  = \App\Models\User::find($validated['user_id'])?->name;

        return response()->json([
            'success' => true,
            'message' => "تم دفع {$amount} ج إلى {$targetName} بنجاح.",
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // receiveFromUser — POST /admin/treasury/receive-from-user
    // ──────────────────────────────────────────────────────────────

    public function receiveFromUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'     => ['nullable', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
            'date'        => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $transaction = $this->treasury->receiveFromUser($validated, auth()->user());
        $amount      = number_format((float) $validated['amount'], 2);
        $targetName  = isset($validated['user_id'])
            ? \App\Models\User::find($validated['user_id'])?->name
            : null;

        return response()->json([
            'success' => true,
            'message' => 'تم استلام ' . $amount . ' ج '
                . ($targetName ? 'من ' . $targetName : '(لحساب الإدارة)') . ' بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // update — PATCH /admin/treasury/{transaction}
    // ──────────────────────────────────────────────────────────────

    public function update(Request $request, TreasuryTransaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note'   => ['nullable', 'string', 'max:500'],
            'date'   => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->validationMessages());

        $this->treasury->update($transaction, $validated);

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
            $html   = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a5', 'portrait');
        return $pdf->download('transaction-' . $transaction->id . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build the base ledger query with all filters applied.
     * Reused by index() and data().
     */
    private function buildLedgerQuery(?string $from, ?string $to, ?string $type, ?string $userId = null)
    {
        return TreasuryTransaction::query()
            ->withinDateRange($from, $to)
            ->ofType($type)
            ->when($userId, function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('recorded_by', $userId)
                        ->orWhere(function ($s1) use ($userId) {
                            $s1->where('source_type', 'manual')
                               ->whereIn('type', ['pay_to_user', 'receive_from_user', 'dain'])
                               ->where('source_id', $userId);
                        })
                        ->orWhere(function ($s2) use ($userId) {
                            $s2->where('source_type', 'settlement')
                               ->whereHas('settlement', fn($s) => $s->where('callcenter_id', $userId));
                        });
                });
            })
            ->with('recordedBy:id,name')
            ->select([
                'id', 'type', 'source_type', 'source_id',
                'amount', 'by_whom', 'note',
                'transaction_date', 'recorded_by', 'created_at',
            ]);
    }

    /**
     * Consistent transaction shape for write-operation responses.
     */
    private function formatTransaction(TreasuryTransaction $tx): array
    {
        return [
            'id'               => $tx->id,
            'transaction_date' => $tx->transaction_date->format('d/m/Y'),
            'type'             => $tx->type,
            'type_label'       => $tx->type_label,
            'type_badge_class' => $tx->type_badge_class,
            'amount'           => number_format((float) $tx->amount, 2),
            'by_whom'          => $tx->by_whom,
            'note'             => $tx->note ?? '—',
        ];
    }
}
