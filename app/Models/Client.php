<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'phone2',
        'code',
    ];

    public function addresses()
    {
        return $this->hasMany(ClientAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(ClientAddress::class)->where('is_default', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // توليد كود تلقائي
    public static function generateCode(): string
    {
        do {
            $code = str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }
}