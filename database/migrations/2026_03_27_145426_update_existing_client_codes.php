<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('clients')->where('code', 'like', 'CLT-%')->get()->each(function ($client) {
            $newCode = str_replace('CLT-', '', $client->code);
            // Check if the new code already exists (unlikely but safe)
            if (!DB::table('clients')->where('code', $newCode)->exists()) {
                DB::table('clients')->where('id', $client->id)->update(['code' => $newCode]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('clients')->get()->each(function ($client) {
            if (strlen($client->code) === 4 && is_numeric($client->code)) {
                $oldCode = 'CLT-' . $client->code;
                if (!DB::table('clients')->where('code', $oldCode)->exists()) {
                    DB::table('clients')->where('id', $client->id)->update(['code' => $oldCode]);
                }
            }
        });
    }
};
