<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN `type` ENUM(
            'delivery_fee_received',
            'cash_received',
            'cash_paid',
            'debt_received',
            'debt_paid',
            'discount',
            'company_revenue',
            'admin_pay',
            'admin_receive',
            'admin_expense'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN `type` ENUM(
            'delivery_fee_received',
            'cash_received',
            'cash_paid',
            'debt_received',
            'debt_paid',
            'discount',
            'company_revenue'
        ) NOT NULL");
    }
};
