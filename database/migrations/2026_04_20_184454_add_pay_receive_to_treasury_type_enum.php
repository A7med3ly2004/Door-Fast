<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'income', 'expense', 'settlement', 'dain', 'discount',
                'pay_to_user', 'receive_from_user',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'income', 'expense', 'settlement', 'dain', 'discount',
            ])->change();
        });
    }
};
