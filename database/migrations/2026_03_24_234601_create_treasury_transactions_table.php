<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Unified ledger table that stores ALL financial entries:
     *
     *   type = 'income'     → manual income added from the Treasury UI
     *   type = 'expense'    → manual expense added from the Treasury UI
     *   type = 'settlement' → auto-created when an admin settles a CC agent
     *
     * source_type + source_id form a lightweight discriminator:
     *   source_type = 'manual'     → source_id is NULL (no parent record)
     *   source_type = 'settlement' → source_id → callcenter_settlements.id
     *
     * by_whom and note are ALWAYS denormalized at write time so the
     * ledger table renders with zero joins. source_id exists only for
     * the "View Details" drill-down modal.
     *
     * Balance formula (single aggregation query, no UNION):
     *   Balance = SUM(amount WHERE type IN ('income','settlement'))
     *           - SUM(amount WHERE type = 'expense')
     */
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();

            // Financial classification
            $table->enum('type', ['income', 'expense', 'settlement']);

            // Source discriminator — tells us WHERE this record originated
            $table->enum('source_type', ['manual', 'settlement'])
                  ->default('manual');

            // FK to callcenter_settlements when source_type = 'settlement'
            // NULL for all manual entries
            $table->foreignId('source_id')
                  ->nullable()
                  ->constrained('callcenter_settlements')
                  ->nullOnDelete(); // if a settlement is ever deleted, keep the ledger row but unlink it

            // Always positive — direction is determined by `type`
            $table->decimal('amount', 10, 2)->unsigned();

            // Denormalized display fields (written once, never updated)
            // For manual: entered by admin in the modal
            // For settlement: auto-set to the CC agent's name
            $table->string('by_whom');

            // For manual: admin's note
            // For settlement: "{agent_name} — {admin_note}" combined string
            $table->text('note')->nullable();

            // The admin who created this record
            $table->foreignId('recorded_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Business date of the transaction.
            // Defaults to today but allows the admin to backdate if needed.
            // Filters in the Treasury UI operate on this column, not created_at.
            $table->date('transaction_date');

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────
            // Most queries filter by type and/or transaction_date
            $table->index(['type', 'transaction_date']);
            $table->index('transaction_date');
            $table->index('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
