<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallcenterShift extends Model
{
    protected $fillable = [
        'callcenter_id',
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

    public function callcenter()
    {
        return $this->belongsTo(User::class, 'callcenter_id');
    }

    public function scopeActive($query)
    {
        [$startOfToday] = \App\Models\Setting::businessDayRange();
        $businessDate   = $startOfToday->toDateString();

        return $query->where('is_active', true)->where('date', $businessDate);
    }
}
