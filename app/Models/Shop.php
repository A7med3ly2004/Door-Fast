<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'code',
        'phone',
        'phone2',
        'phone3',
        'phone4',
        'address',
        'notes',
        'shop_category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function generateCode(): string
    {
        $baseNumber = 8000;
        $lastCode = static::get(['code'])
            ->filter(fn($s) => is_numeric($s->code))
            ->map(fn($s) => (int) $s->code)
            ->max();

        if ($lastCode && $lastCode >= $baseNumber) {
            return (string)($lastCode + 1);
        }

        return (string)($baseNumber + 1);
    }
}