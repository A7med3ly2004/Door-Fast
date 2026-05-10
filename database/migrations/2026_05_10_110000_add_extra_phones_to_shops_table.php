<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('phone2')->nullable()->after('phone');
            $table->string('phone3')->nullable()->after('phone2');
            $table->string('phone4')->nullable()->after('phone3');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['phone2', 'phone3', 'phone4']);
        });
    }
};
