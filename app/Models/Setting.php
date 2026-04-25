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
     * Get the bounds of the current business day.
     * The day runs from 00:00:00 to 23:59:59.
     * 
     * @param \Carbon\Carbon|null $date
     * @return array [\Carbon\Carbon $start, \Carbon\Carbon $end]
     */
    public static function businessDayRange(\Carbon\Carbon $date = null): array
    {
        $date = $date ? $date->copy() : now();

        $start = $date->copy()->startOfDay();           // 00:00:00
        $end   = $date->copy()->endOfDay();              // 23:59:59

        return [$start, $end];
    }
}