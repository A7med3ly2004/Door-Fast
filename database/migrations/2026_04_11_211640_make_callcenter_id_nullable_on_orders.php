<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make callcenter_id nullable so admin-created orders
     * (callcenter_id = null) can be stored without a CC agent.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('callcenter_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Note: reverting may fail if null rows exist — handle manually if needed
            $table->unsignedBigInteger('callcenter_id')->nullable(false)->change();
        });
    }
};
