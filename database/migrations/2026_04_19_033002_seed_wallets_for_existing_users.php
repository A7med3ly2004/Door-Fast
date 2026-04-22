<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')
            ->whereNotIn('id', DB::table('wallets')->select('user_id'))
            ->pluck('id');

        $now = now();

        $rows = $users->map(fn($id) => [
            'user_id'    => $id,
            'balance'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if ($rows) {
            DB::table('wallets')->insert($rows);
        }
    }

    public function down(): void
    {
        // الحذف سيتم بـ drop wallets table
    }
};
