<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'order_id',
        'order_number',
        'message',
        'is_read',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
