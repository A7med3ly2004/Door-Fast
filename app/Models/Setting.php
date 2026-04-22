<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // جيب setting بالـ key
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // احفظ أو عدّل setting
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get the bounds of the current business day, based on the `day_end_time` configuration.
     * 
     * @param \Carbon\Carbon|null $date
     * @return array [\Carbon\Carbon $start, \Carbon\Carbon $end]
     */
    public static function businessDayRange(\Carbon\Carbon $date = null): array
    {
        $date = $date ? $date->copy() : now();
        $time = self::get('day_end_time', '00:00'); // 'H:i'
        
        list($h, $m) = explode(':', $time);
        
        // Target boundary for this specific day
        $boundary = $date->copy()->startOfDay()->addHours($h)->addMinutes($m);
        
        if ($date->lt($boundary)) {
            // If the current time is before the boundary, we are in the "previous" business day
            $start = $boundary->copy()->subDay();
            $end = $boundary->copy()->subSecond();
        } else {
            // We are in the current business day
            $start = $boundary->copy();
            $end = $boundary->copy()->addDay()->subSecond();
        }
        
        return [$start, $end];
    }
}