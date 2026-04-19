<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table is the source of truth for every settlement action
     * performed by an Admin against a Call Center agent.
     *
     * Each row here will have a corresponding mirror row in
     * treasury_transactions (type='settlement') created atomically
     * inside CallCenterManagementController@settle.
     */
    public function up(): void
    {
        Schema::create('callcenter_settlements', function (Blueprint $table) {
            $table->id();

            // The call center agent being settled
            $table->foreignId('callcenter_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // The admin who performed the settlement
            $table->foreignId('settled_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Settlement amount — always positive
            $table->decimal('amount', 10, 2)->unsigned();

            // Admin's note about the settlement (optional)
            $table->text('note')->nullable();

            // Explicit settlement timestamp (separate from created_at
            // so we can display "settled at" independently of record creation)
            $table->timestamp('settled_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callcenter_settlements');
    }
};
