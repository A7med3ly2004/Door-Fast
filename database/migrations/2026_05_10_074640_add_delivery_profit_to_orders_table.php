<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('delivery_tier_number')
                  ->default(0)
                  ->after('delivered_at')
                  ->comment('رقم الشريحة وقت التوصيل');

            $table->decimal('delivery_profit', 10, 2)
                  ->default(0)
                  ->after('delivery_tier_number')
                  ->comment('مبلغ الربح للطلب الواحد وقت التوصيل');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_tier_number', 'delivery_profit']);
        });
    }
};
