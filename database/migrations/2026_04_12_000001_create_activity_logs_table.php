<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event');                              // machine-readable key
            $table->text('description');                          // human-readable Arabic
            $table->string('subject_type')->nullable();           // e.g. 'order', 'client'
            $table->unsignedBigInteger('subject_id')->nullable(); // e.g. order id
            $table->string('subject_label')->nullable();          // e.g. order number / client name
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('causer_role')->nullable();            // admin, callcenter, delivery …
            $table->json('properties')->nullable();               // extra payload
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index(['causer_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
