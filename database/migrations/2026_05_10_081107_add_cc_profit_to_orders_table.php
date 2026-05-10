<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('cc_tier_number')
                  ->default(0)
                  ->after('delivery_profit')
                  ->comment('رقم شريحة الكول سينتر وقت إنشاء الطلب');

            $table->decimal('cc_profit', 10, 2)
                  ->default(0)
                  ->after('cc_tier_number')
                  ->comment('مبلغ ربح الطلب الواحد للكول سينتر وقت الإنشاء');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cc_tier_number', 'cc_profit']);
        });
    }
};
