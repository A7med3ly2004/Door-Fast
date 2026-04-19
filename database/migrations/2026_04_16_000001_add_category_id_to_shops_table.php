<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->foreignId('shop_category_id')->nullable()->after('id')->constrained('shop_categories')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['shop_category_id']);
            $table->dropColumn('shop_category_id');
        });
    }
};
