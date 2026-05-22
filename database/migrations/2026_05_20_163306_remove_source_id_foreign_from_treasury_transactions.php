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
        $table->dropForeign(['source_id']);
    });
}

public function down(): void
{
    Schema::table('treasury_transactions', function (Blueprint $table) {
        $table->foreign('source_id')
              ->references('id')
              ->on('callcenter_settlements')
              ->onDelete('set null');
    });
}
};
