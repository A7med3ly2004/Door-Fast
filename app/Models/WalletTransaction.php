<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'direction',
        'balance_after',
        'description',
        'related_wallet_id',
        'order_id',
        'created_by',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'balance_after'    => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    // ─── العلاقات ──────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function relatedWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /** هل هذه العملية دخل (مدين)؟ */
    public function isDebit(): bool
    {
        return $this->direction === 'debit';
    }

    /** هل هذه العملية خروج (دائن)؟ */
    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    /** الأنواع بالعربي */
    public static function typeLabels(): array
    {
        return [
            'delivery_fee_received' => 'استلام رسوم توصيل',
            'cash_received'         => 'استلام نقدي',
            'cash_paid'             => 'دفع نقدي',
            'debt_received'         => 'استلام مديونية',
            'debt_paid'             => 'دفع مديونية',
            'discount'              => 'خصم',
            'company_revenue'       => 'إيراد للشركة',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }
}
