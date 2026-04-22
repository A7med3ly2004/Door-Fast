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
                if ($user->role === 'callcenter') {
                    $user->code = self::generateUniqueRoleCode('C');
                } elseif (in_array($user->role, ['delivery', 'reserve_delivery'])) {
                    $user->code = self::generateUniqueRoleCode('D');
                }
            }
        });
    }

    public static function generateUniqueRoleCode($prefix)
    {
        // Find the numeric part from codes like "D-001"
        $lastUser = self::where('code', 'LIKE', "{$prefix}-%")
            ->get()
            ->filter(fn($u) => preg_match('/^' . $prefix . '-(\d+)$/', $u->code))
            ->map(function ($u) use ($prefix) {
                preg_match('/^' . $prefix . '-(\d+)$/', $u->code, $matches);
                return (int) $matches[1];
            })
            ->max();

        $number = ($lastUser ?? 0) + 1;
        return $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
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

    // علاقات الدلفري
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
        return $this->hasOne(Shift::class, 'delivery_id')
                    ->where('is_active', true)
                    ->whereDate('date', today());
    }

    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeCallcenters($query)
    {
        return $query->where('role', 'callcenter');
    }

    public function scopeDeliveries($query)
    {
        return $query->where('role', 'delivery');
    }

    public function scopeReserveDeliveries($query)
    {
        return $query->where('role', 'reserve_delivery');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCallcenter(): bool
    {
        return $this->role === 'callcenter';
    }

    public function isDelivery(): bool
    {
        return $this->role === 'delivery';
    }

    public function isReserveDelivery(): bool
    {
        return $this->role === 'reserve_delivery';
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