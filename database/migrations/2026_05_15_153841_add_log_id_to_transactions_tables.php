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
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('log_id')->nullable()->after('id');
            $table->foreign('log_id')->references('id')->on('activity_logs')->nullOnDelete();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('log_id')->nullable()->after('id');
            $table->foreign('log_id')->references('id')->on('activity_logs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
    }
};
