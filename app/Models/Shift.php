<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'delivery_id',
        'date',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected $casts = [
        'date'       => 'date',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function delivery()
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereDate('date', today());
    }
}