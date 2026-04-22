<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->enum('type', [
                'delivery_fee_received',  // استلام رسوم توصيل
                'cash_received',          // استلام نقدي
                'cash_paid',              // دفع نقدي
                'debt_received',          // استلام مديونية
                'debt_paid',              // دفع مديونية
                'discount',               // خصم
                'company_revenue',        // إيراد للشركة
            ]);
            $table->decimal('amount', 12, 2);              // دائماً موجب
            $table->enum('direction', ['debit', 'credit']); // debit = دخل, credit = خروج
            $table->decimal('balance_after', 12, 2);        // الرصيد بعد العملية (Running Balance)
            $table->string('description')->nullable();       // ملاحظة / تعريف
            $table->foreignId('related_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('transaction_date');
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
