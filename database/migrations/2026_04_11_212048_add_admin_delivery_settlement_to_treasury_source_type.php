<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'admin_delivery_settlement' to the source_type ENUM
     * so admin → delivery settlement transactions can be stored.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE treasury_transactions
            MODIFY COLUMN source_type
            ENUM('manual','settlement','admin_delivery_settlement')
            NOT NULL DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasury_source_type', function (Blueprint $table) {
            //
        });
    }
};
