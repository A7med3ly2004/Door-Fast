<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // جيب setting بالـ key (مع Cache لمدة 5 دقائق)
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    // احفظ أو عدّل setting وامسح الـ Cache الخاص بيه
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Get the bounds of the current business day.
     *
     * When business_day_start_hour = 0 (default), the day runs 00:00:00 → 23:59:59
     * (identical to the old behaviour — no breaking change).
     *
     * When a non-zero hour is configured (e.g. 5), the day slides:
     *   - 03:00 on 5/5  →  start = 4/5 05:00,  end = 5/5 04:59
     *   - 10:00 on 5/5  →  start = 5/5 05:00,  end = 6/5 04:59
     *   - 23:30 on 5/5  →  start = 5/5 05:00,  end = 6/5 04:59
     *
     * @param \Carbon\Carbon|null $date
     * @return array [\Carbon\Carbon $start, \Carbon\Carbon $end]
     */
    public static function businessDayRange(\Carbon\Carbon $date = null): array
    {
        $date      = $date ? $date->copy() : now();
        $startHour = (int) static::get('business_day_start_hour', 0);

        if ($startHour === 0) {
            // الوضع الافتراضي — منتصف الليل (سلوك مطابق للسابق)
            return [
                $date->copy()->startOfDay(),  // 00:00:00
                $date->copy()->endOfDay(),    // 23:59:59
            ];
        }

        // يوم العمل المنزلق
        // إذا الوقت الحالي قبل startHour، فنحن لا نزال في "يوم الأمس"
        if ($date->hour < $startHour) {
            $dayStart = $date->copy()->subDay()->setTime($startHour, 0, 0);
        } else {
            $dayStart = $date->copy()->setTime($startHour, 0, 0);
        }

        // نهاية اليوم = بداية اليوم التالي - ثانية واحدة
        $dayEnd = $dayStart->copy()->addDay()->subSecond();

        return [$dayStart, $dayEnd];
    }
}