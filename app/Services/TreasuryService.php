<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * TreasuryService
 *
 * مسؤوليته: تنفيذ عمليات الخزينة المالية.
 * - يستقبل validated data فقط (لا validation هنا).
 * - يُرجع TreasuryTransaction في كل عملية.
 * - الـ Controller مسؤول عن الـ validation وبناء الـ Response.
 */
class TreasuryService
{
    // ──────────────────────────────────────────────────────────────
    // addIncome — إضافة إيراد في الخزينة
    // ──────────────────────────────────────────────────────────────

    /**
     * @param array{by_whom: string, amount: float|string, note: ?string, date: ?string} $data
     * @param int $recordedBy  auth()->id()
     */
    public function addIncome(array $data, int $recordedBy): TreasuryTransaction
    {
        $transaction = TreasuryTransaction::createManual(
            type:            'income',
            byWhom:          $data['by_whom'],
            amount:          (float) $data['amount'],
            note:            $data['note'] ?? null,
            recordedBy:      $recordedBy,
            transactionDate: $data['date'] ?? null,
        );

        $log = ActivityLog::log(
            event:        'treasury.income',
            description:  'تم إضافة وارد في الخزينة — ' . $data['by_whom'],
            subjectType:  'treasury',
            subjectId:    $transaction->id,
            subjectLabel: number_format((float) $data['amount'], 2) . ' ج',
            properties:   [
                'by_whom' => $data['by_whom'],
                'amount'  => $data['amount'],
                'note'    => $data['note'] ?? null,
            ]
        );

        $transaction->update(['log_id' => $log->id]);

        return $transaction;
    }


    // ──────────────────────────────────────────────────────────────
    // addDain — إضافة مديونية (صرف دين)
    // ──────────────────────────────────────────────────────────────

    /**
     * @param array{callcenter_id: ?int, delivery_id: ?int, amount: float|string, note: ?string, date: ?string} $data
     * @param int $recordedBy  auth()->id()
     *
     * Precondition: exactly one of callcenter_id / delivery_id is set
     *               (Controller validates this before calling).
     */
    public function addDain(array $data, int $recordedBy): TreasuryTransaction
    {
        $userId = $data['callcenter_id'] ?? $data['delivery_id'];
        $user   = User::find($userId);

        $transaction = TreasuryTransaction::create([
            'type'             => 'dain',
            'source_type'      => 'manual',
            'source_id'        => $user->id,
            'amount'           => (float) $data['amount'],
            'by_whom'          => $user->name,
            'note'             => $data['note'] ?? null,
            'recorded_by'      => $recordedBy,
            'transaction_date' => $data['date'] ?? now()->toDateString(),
        ]);

        $log = ActivityLog::log(
            event:        'treasury.dain',
            description:  'تم إضافة صرف مديونية في الخزينة — ' . $user->name,
            subjectType:  'treasury',
            subjectId:    $transaction->id,
            subjectLabel: number_format((float) $data['amount'], 2) . ' ج',
            properties:   [
                'user_id' => $user->id,
                'role'    => $user->role,
                'amount'  => $data['amount'],
                'note'    => $data['note'] ?? null,
            ]
        );

        $transaction->update(['log_id' => $log->id]);

        return $transaction;
    }

    // ──────────────────────────────────────────────────────────────
    // payToUser — دفع نقدي من خزينة الأدمن إلى خزينة موظف
    // ──────────────────────────────────────────────────────────────

    /**
     * @param array{user_id: int, amount: float|string, description: ?string, date: ?string} $data
     * @param User $admin  auth()->user()
     *
     * @return TreasuryTransaction  سجل الدفع في الخزينة
     */
    public function payToUser(array $data, User $admin): TreasuryTransaction
    {
        $targetUser    = User::findOrFail($data['user_id']);
        $walletService = app(WalletService::class);
        $date          = $data['date'] ?? now()->toDateString();
        $description   = $data['description'] ?? ('دفع نقدي إلى ' . $targetUser->name);

        $treasuryTx = DB::transaction(function () use (
            $admin, $targetUser, $walletService, $data, $date, $description
        ): TreasuryTransaction {
            $targetWallet = $targetUser->getOrCreateWallet();

            // نوع المعاملة يعتمد على دور المستخدم المستهدف
            // إذا كان المستهدف أدمن → admin_receive حتى يظهر في كشف حسابه الخاص
            // إذا كان موظفاً → cash_received
            $creditType = $targetUser->role === 'admin' ? 'admin_receive' : 'cash_received';

            $walletService->credit(
                wallet:      $targetWallet,
                amount:      (float) $data['amount'],
                type:        $creditType,
                description: 'استلام نقدي من الإدارة' . ($description ? ' — ' . $description : ''),
                createdBy:   $admin->id,
                date:        $date
            );

            // سجل العملية في الخزينة المستقلة
            return TreasuryTransaction::create([
                'type'             => 'pay_to_user',
                'source_type'      => 'manual',
                'source_id'        => $targetUser->id,
                'amount'           => (float) $data['amount'],
                'by_whom'          => $targetUser->name,
                'note'             => $data['description'] ?? ('دفع نقدي إلى ' . $targetUser->name),
                'recorded_by'      => $admin->id,
                'transaction_date' => $date,
            ]);
        });

        $log = ActivityLog::log(
            event:        'treasury.pay_to_user',
            description:  'دفع نقدي إلى ' . $targetUser->name . ' — ' . number_format((float) $data['amount'], 2) . ' ج',
            subjectType:  'treasury',
            subjectId:    $targetUser->id,
            subjectLabel: $targetUser->name,
            properties:   [
                'user_id' => $targetUser->id,
                'amount'  => $data['amount'],
                'note'    => $data['description'] ?? null,
            ]
        );

        $treasuryTx->update(['log_id' => $log->id]);

        return $treasuryTx;
    }

    // ──────────────────────────────────────────────────────────────
    // receiveFromUser — استلام نقدي من موظف إلى خزينة الأدمن
    // ──────────────────────────────────────────────────────────────

    /**
     * @param array{user_id: ?int, amount: float|string, description: ?string, date: ?string} $data
     * @param User $admin  auth()->user()
     *
     * @return TreasuryTransaction  سجل الاستلام في الخزينة
     */
    public function receiveFromUser(array $data, User $admin): TreasuryTransaction
    {
        $targetUser    = isset($data['user_id']) ? User::find($data['user_id']) : null;
        $walletService = app(WalletService::class);
        $date          = $data['date'] ?? now()->toDateString();

        $defaultDesc = $targetUser
            ? ('استلام نقدي من ' . $targetUser->name)
            : 'استلام نقدي لحساب الإدارة';
        $description = $data['description'] ?? $defaultDesc;

        $treasuryTx = DB::transaction(function () use (
            $admin, $targetUser, $walletService, $data, $date, $description
        ): TreasuryTransaction {

            if ($targetUser) {
                // نوع المعاملة يعتمد على دور المستخدم المستهدف
                // إذا كان المستهدف أدمن → admin_pay حتى يظهر في كشف حسابه الخاص
                // إذا كان موظفاً → cash_paid
                $debitType = $targetUser->role === 'admin' ? 'admin_pay' : 'cash_paid';

                $targetWallet = $targetUser->getOrCreateWallet();
                $walletService->debit(
                    wallet:      $targetWallet,
                    amount:      (float) $data['amount'],
                    type:        $debitType,
                    description: 'دفع نقدي للإدارة' . ($description ? ' — ' . $description : ''),
                    createdBy:   $admin->id,
                    date:        $date
                );
            }

            // سجل العملية في الخزينة المستقلة
            return TreasuryTransaction::create([
                'type'             => 'receive_from_user',
                'source_type'      => 'manual',
                'source_id'        => $targetUser?->id,
                'amount'           => (float) $data['amount'],
                'by_whom'          => $targetUser ? $targetUser->name : 'بدون موظف',
                'note'             => $description,
                'recorded_by'      => $admin->id,
                'transaction_date' => $date,
            ]);
        });

        $log = ActivityLog::log(
            event:        'treasury.receive_from_user',
            description:  $targetUser
                ? ('استلام نقدي من ' . $targetUser->name . ' — ' . number_format((float) $data['amount'], 2) . ' ج')
                : ('استلام نقدي (بدون موظف) — ' . number_format((float) $data['amount'], 2) . ' ج'),
            subjectType:  'treasury',
            subjectId:    $targetUser ? $targetUser->id : $admin->id,
            subjectLabel: $targetUser ? $targetUser->name : 'الإدارة',
            properties:   [
                'user_id' => $targetUser?->id,
                'amount'  => $data['amount'],
                'note'    => $data['description'] ?? null,
            ]
        );

        $treasuryTx->update(['log_id' => $log->id]);

        return $treasuryTx;
    }

    // ──────────────────────────────────────────────────────────────
    // update — تعديل معاملة مالية
    // ──────────────────────────────────────────────────────────────

    /**
     * @param array{amount: float|string, note: ?string, date: ?string} $data
     */
    public function update(TreasuryTransaction $transaction, array $data): TreasuryTransaction
    {
        $transaction->update([
            'amount'           => $data['amount'],
            'note'             => $data['note'] ?? $transaction->note,
            'transaction_date' => $data['date'] ?? $transaction->transaction_date,
        ]);

        $log = ActivityLog::log(
            event:        'treasury.updated',
            description:  'تم تعديل معاملة مالية',
            subjectType:  'treasury',
            subjectId:    $transaction->id,
            subjectLabel: number_format((float) $transaction->amount, 2) . ' ج'
        );

        $transaction->update(['log_id' => $log->id]);

        return $transaction->fresh();
    }
}
