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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE treasury_transactions MODIFY COLUMN type ENUM('income', 'expense', 'settlement', 'dain') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Careful here: if there are any 'dain' records, this will fail or cause data loss.
        // For local dev/internal system, we just revert the enum.
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE treasury_transactions MODIFY COLUMN type ENUM('income', 'expense', 'settlement') NOT NULL");
    }
};
