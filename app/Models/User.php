<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'phone',
        'personal_phone',
        'fcm_token',
        'code',
        'is_active',
        'cc_shift_enabled',
        'unsettled_value',
        'unsettled_fees',
        'incentive_slices',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->code)) {
                if ($user->role === 'admin') {
                    $user->code = self::generateNumericCode(100, ['admin']);
                } elseif ($user->role === 'callcenter') {
                    $user->code = self::generateNumericCode(3000, ['callcenter']);
                } elseif (in_array($user->role, ['delivery', 'reserve_delivery'])) {
                    $user->code = self::generateNumericCode(4000, ['delivery', 'reserve_delivery']);
                }
            }
        });
    }

    public static function generateNumericCode($baseNumber, $roles)
    {
        $lastCode = self::whereIn('role', $roles)
            ->get(['code'])
            ->filter(fn($u) => is_numeric($u->code))
            ->map(fn($u) => (int) $u->code)
            ->max();

        if ($lastCode && $lastCode >= $baseNumber) {
            return (string)($lastCode + 1);
        }

        return (string)($baseNumber + 1);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'         => 'hashed',
            'is_active'        => 'boolean',
            'cc_shift_enabled' => 'boolean',
            'incentive_slices' => 'array',
        ];
    }

    // علاقات الكول سينتر
    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'callcenter_id');
    }

    // علاقات المندوب
    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_id');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'delivery_id');
    }

    public function activeShift()
    {
        [$startOfToday] = \App\Models\Setting::businessDayRange();
        $businessDate   = $startOfToday->toDateString();

        return $this->hasOne(Shift::class, 'delivery_id')
                    ->where('is_active', true)
                    ->where('date', $businessDate);
    }

    // Scopes

    public function scopeCallcenters($query)
    {
        return $query->where('role', 'callcenter');
    }

    public function scopeDeliveries($query)
    {
        return $query->where('role', 'delivery');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // ─── الخزينة (Wallet) ─────────────────────────────────────

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * إرجاع خزينة المستخدم — تُنشأ تلقائياً إذا لم توجد.
     */
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create(['balance' => 0]);
    }
}