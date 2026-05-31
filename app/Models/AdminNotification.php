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
        'audience',            // 'admin' | 'callcenter' | 'all'
        'is_read_by_admin',
        'is_read_by_callcenter',
    ];

    protected $casts = [
        'is_read'               => 'boolean',
        'is_read_by_admin'      => 'boolean',
        'is_read_by_callcenter' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
