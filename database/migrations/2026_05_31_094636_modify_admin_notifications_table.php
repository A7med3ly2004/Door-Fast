<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds:
     *  - audience: who sees this notification ('admin' | 'callcenter' | 'all')
     *  - is_read_by_admin: whether admins have marked it read
     *  - is_read_by_callcenter: whether callcenter users have marked it read
     */
    public function up(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            // 'admin'       => visible only to admins
            // 'callcenter'  => visible only to callcenter
            // 'all'         => visible to both
            $table->string('audience')->default('all')->after('is_read');

            // Separate read-status per audience group
            $table->boolean('is_read_by_admin')->default(false)->after('audience');
            $table->boolean('is_read_by_callcenter')->default(false)->after('is_read_by_admin');
        });

        // Back-fill: treat the current is_read as read for both groups
        \DB::table('admin_notifications')->where('is_read', true)->update([
            'is_read_by_admin'       => true,
            'is_read_by_callcenter'  => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropColumn(['audience', 'is_read_by_admin', 'is_read_by_callcenter']);
        });
    }
};
