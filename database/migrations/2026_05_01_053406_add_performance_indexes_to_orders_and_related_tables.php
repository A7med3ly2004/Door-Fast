<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // جدول orders
        // الأعمدة التالية عندها indexes بالفعل (من foreign keys أو migrations سابقة):
        // status, delivery_id, callcenter_id, accepted_at, delivered_at, sent_to_delivery_at
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
        });

        // جدول order_items
        // shop_id عنده index بالفعل (من foreign key)

        // جدول shifts - composite index للفلترة السريعة
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['delivery_id', 'date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['delivery_id', 'date', 'is_active']);
        });
    }
};
