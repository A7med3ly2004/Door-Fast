<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * إضافة للرصيد (مدين — دخل للخزينة)
     */
    public function credit(
        Wallet  $wallet,
        float   $amount,
        string  $type,
        string  $description,
        int     $createdBy,
        ?int    $relatedWalletId = null,
        ?int    $orderId = null,
        ?string $date = null
    ): WalletTransaction {
        return $this->record(
            wallet:          $wallet,
            direction:       'debit',   // مدين = دخل
            amount:          $amount,
            type:            $type,
            description:     $description,
            createdBy:       $createdBy,
            relatedWalletId: $relatedWalletId,
            orderId:         $orderId,
            date:            $date ?? now()->toDateString(),
        );
    }

    /**
     * خصم من الرصيد (دائن — خروج من الخزينة)
     */
    public function debit(
        Wallet  $wallet,
        float   $amount,
        string  $type,
        string  $description,
        int     $createdBy,
        ?int    $relatedWalletId = null,
        ?int    $orderId = null,
        ?string $date = null
    ): WalletTransaction {
        return $this->record(
            wallet:          $wallet,
            direction:       'credit',  // دائن = خروج
            amount:          $amount,
            type:            $type,
            description:     $description,
            createdBy:       $createdBy,
            relatedWalletId: $relatedWalletId,
            orderId:         $orderId,
            date:            $date ?? now()->toDateString(),
        );
    }

    /**
     * الدالة الداخلية المشتركة — تحسب balance_after وتحدّث wallet.balance
     * تعمل داخل DB::transaction لضمان consistency
     */
    private function record(
        Wallet  $wallet,
        string  $direction,
        float   $amount,
        string  $type,
        string  $description,
        int     $createdBy,
        ?int    $relatedWalletId,
        ?int    $orderId,
        string  $date
    ): WalletTransaction {
        return DB::transaction(function () use (
            $wallet, $direction, $amount, $type,
            $description, $createdBy, $relatedWalletId, $orderId, $date
        ) {
            // قفل الصف لمنع التعديل المتزامن
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            // حساب الرصيد الجديد
            $currentBalance = (float) $wallet->balance;
            $balanceAfter = $direction === 'debit'
                ? $currentBalance + $amount    // إضافة
                : $currentBalance - $amount;   // خصم

            // تحديث رصيد الخزينة
            $wallet->update(['balance' => $balanceAfter]);

            // تسجيل العملية
            return WalletTransaction::create([
                'wallet_id'         => $wallet->id,
                'type'              => $type,
                'amount'            => $amount,
                'direction'         => $direction,
                'balance_after'     => $balanceAfter,
                'description'       => $description,
                'related_wallet_id' => $relatedWalletId,
                'order_id'          => $orderId,
                'created_by'        => $createdBy,
                'transaction_date'  => $date,
            ]);
        });
    }
}
