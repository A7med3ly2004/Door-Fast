<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    protected $fillable = ['name'];

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }
}
