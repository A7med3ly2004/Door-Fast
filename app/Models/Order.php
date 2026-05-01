<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'callcenter_id',
        'delivery_id',
        'is_delivery_chosen',
        'client_id',
        'client_address',
        'send_to_phone',
        'send_to_address',
        'notes',
        'delivery_fee',
        'discount',
        'discount_type',
        'total',
        'status',
        'is_settled',
        'sent_to_delivery_at',
        'accepted_at',
        'delivered_at',
    ];

    protected $casts = [
        'is_settled'          => 'boolean',
        'is_delivery_chosen'  => 'boolean',
        'delivery_fee'        => 'decimal:2',
        'discount'            => 'decimal:2',
        'total'               => 'decimal:2',
        'sent_to_delivery_at' => 'datetime',
        'accepted_at'         => 'datetime',
        'delivered_at'        => 'datetime',
    ];

    // Relationships
    public function callcenter()
    {
        return $this->belongsTo(User::class, 'callcenter_id');
    }

    public function delivery()
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }

    public function adminNotifications()
    {
        return $this->hasMany(\App\Models\AdminNotification::class);
    }

    // توليد رقم الأوردر تلقائياً
    public static function generateNumber(): string
    {
        $last = self::orderBy('id', 'desc')->first();
        $num  = $last ? (intval(substr($last->order_number, 4)) + 1) : 1;
        
        $orderNumber = 'ORD-' . str_pad($num, 7, '0', STR_PAD_LEFT);
        while (self::where('order_number', $orderNumber)->exists()) {
            $num++;
            $orderNumber = 'ORD-' . str_pad($num, 7, '0', STR_PAD_LEFT);
        }
        
        return $orderNumber;
    }

    // حساب الإجمالي
    public function calculateTotal(): float
    {
        $itemsTotal = $this->items->sum('total');
        $discount   = $this->discount_type === 'percent'
            ? ($itemsTotal * $this->discount / 100)
            : $this->discount;

        return $itemsTotal + $this->delivery_fee - $discount;
    }
}