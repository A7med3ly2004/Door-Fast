<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users 
            MODIFY COLUMN role 
            ENUM('admin', 'callcenter', 'delivery', 'reserve_delivery') 
            NOT NULL DEFAULT 'delivery'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users 
            MODIFY COLUMN role 
            ENUM('admin', 'callcenter', 'delivery') 
            NOT NULL DEFAULT 'delivery'
        ");
    }
};