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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('callcenter_id')->constrained('users');
            $table->foreignId('delivery_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('client_address');
            $table->string('send_to_phone')->nullable();
            $table->string('send_to_address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->enum('discount_type', ['amount', 'percent'])->default('amount');
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['pending', 'received', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('sent_to_delivery_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
