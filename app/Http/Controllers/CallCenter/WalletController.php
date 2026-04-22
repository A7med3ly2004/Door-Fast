<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WalletController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // index — SPA + full page
    // ──────────────────────────────────────────────────────────────

    public function index(Request $request): View|JsonResponse
    {
        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->active()
            ->get(['id', 'name']);

        $data = compact('deliveries');

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('callcenter.wallet.partials.content', $data)->render(),
                'title'      => 'كشف حسابي',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('callcenter.wallet.index', $data);
    }

    // ──────────────────────────────────────────────────────────────
    // statement — JSON: KPIs + transaction list for logged-in CC
    // ──────────────────────────────────────────────────────────────

    public function statement(Request $request): JsonResponse
    {
        $request->validate([
            'from'        => ['nullable', 'date_format:Y-m-d'],
            'to'          => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'delivery_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user   = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $from       = $request->input('from');
        $to         = $request->input('to');
        $deliveryId = $request->input('delivery_id');

        $query = WalletTransaction::where('wallet_id', $wallet->id);

        if ($from) $query->where('transaction_date', '>=', $from);
        if ($to)   $query->where('transaction_date', '<=', $to);

        if ($deliveryId) {
            $deliveryWallet = User::find($deliveryId)?->wallet;
            if ($deliveryWallet) {
                $query->where('related_wallet_id', $deliveryWallet->id);
            }
        }

        // KPIs
        $totals = (clone $query)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        // Transactions
        $transactions = (clone $query)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn(WalletTransaction $tx) => [
                'id'               => $tx->id,
                'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                'description'      => $tx->description ?? '—',
                'type_label'       => $tx->type_label,
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

    // ──────────────────────────────────────────────────────────────
    // payToDelivery — دفع نقدي لمندوب
    // ──────────────────────────────────────────────────────────────

    public function payToDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_id' => ['required', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
            'date'        => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->messages());

        $cc       = auth()->user();
        $delivery = User::findOrFail($validated['delivery_id']);
        $service  = app(WalletService::class);
        $date     = $validated['date'] ?? now()->toDateString();
        $desc     = $validated['description'] ?? null;

        DB::transaction(function () use ($cc, $delivery, $service, $validated, $date, $desc) {
            $ccWallet  = $cc->getOrCreateWallet();
            $delWallet = $delivery->getOrCreateWallet();

            $service->debit(
                wallet:          $ccWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_paid',
                description:     'دفع نقدي إلى ' . $delivery->name . ($desc ? ' — ' . $desc : ''),
                createdBy:       $cc->id,
                relatedWalletId: $delWallet->id,
                date:            $date,
            );

            $service->credit(
                wallet:          $delWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_received',
                description:     'استلام نقدي من ' . $cc->name . ($desc ? ' — ' . $desc : ''),
                createdBy:       $cc->id,
                relatedWalletId: $ccWallet->id,
                date:            $date,
            );
        });

        ActivityLog::log(
            event:        'wallet.cc_pay_delivery',
            description:  $cc->name . ' دفع ' . number_format((float) $validated['amount'], 2) . ' ج إلى ' . $delivery->name,
            subjectType:  'wallet',
            subjectId:    $delivery->id,
            subjectLabel: $delivery->name,
            properties:   ['amount' => $validated['amount'], 'note' => $desc],
            causerId:     $cc->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم دفع ' . number_format((float) $validated['amount'], 2) . ' ج إلى ' . $delivery->name . ' بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // receiveFromDelivery — استلام نقدي من مندوب
    // ──────────────────────────────────────────────────────────────

    public function receiveFromDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_id' => ['required', 'exists:users,id'],
            'amount'      => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
            'date'        => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->messages());

        $cc       = auth()->user();
        $delivery = User::findOrFail($validated['delivery_id']);
        $service  = app(WalletService::class);
        $date     = $validated['date'] ?? now()->toDateString();
        $desc     = $validated['description'] ?? null;

        DB::transaction(function () use ($cc, $delivery, $service, $validated, $date, $desc) {
            $ccWallet  = $cc->getOrCreateWallet();
            $delWallet = $delivery->getOrCreateWallet();

            $service->credit(
                wallet:          $ccWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_received',
                description:     'استلام نقدي من ' . $delivery->name . ($desc ? ' — ' . $desc : ''),
                createdBy:       $cc->id,
                relatedWalletId: $delWallet->id,
                date:            $date,
            );

            $service->debit(
                wallet:          $delWallet,
                amount:          (float) $validated['amount'],
                type:            'cash_paid',
                description:     'دفع نقدي إلى ' . $cc->name . ($desc ? ' — ' . $desc : ''),
                createdBy:       $cc->id,
                relatedWalletId: $ccWallet->id,
                date:            $date,
            );
        });

        ActivityLog::log(
            event:        'wallet.cc_receive_delivery',
            description:  $cc->name . ' استلم ' . number_format((float) $validated['amount'], 2) . ' ج من ' . $delivery->name,
            subjectType:  'wallet',
            subjectId:    $delivery->id,
            subjectLabel: $delivery->name,
            properties:   ['amount' => $validated['amount'], 'note' => $desc],
            causerId:     $cc->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم استلام ' . number_format((float) $validated['amount'], 2) . ' ج من ' . $delivery->name . ' بنجاح.',
        ], 201);
    }

    private function messages(): array
    {
        return [
            'delivery_id.required' => 'يجب اختيار المندوب.',
            'delivery_id.exists'   => 'المندوب المختار غير موجود.',
            'amount.required'      => 'حقل المبلغ مطلوب.',
            'amount.numeric'       => 'يجب أن يكون المبلغ رقمًا.',
            'amount.gt'            => 'يجب أن يكون المبلغ أكبر من صفر.',
            'amount.max'           => 'المبلغ كبير جدًا.',
            'description.max'      => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
            'date.date_format'     => 'صيغة التاريخ غير صحيحة.',
            'date.before_or_equal' => 'لا يمكن إدخال تاريخ مستقبلي.',
        ];
    }
}
