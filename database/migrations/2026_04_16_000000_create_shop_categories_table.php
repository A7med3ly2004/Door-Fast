<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shop_categories', function (Blueprint $column) {
            $column->id();
            $column->string('name');
            $column->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_categories');
    }
};
