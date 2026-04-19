<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CallcenterSettlement extends Model
{
    protected $fillable = [
        'callcenter_id',
        'settled_by',
        'amount',
        'note',
        'settled_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    /**
     * The call center agent who was settled.
     */
    public function callcenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callcenter_id');
    }

    /**
     * The admin who performed the settlement.
     */
    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * The corresponding treasury ledger entry.
     *
     * Every settlement has exactly one mirror row in treasury_transactions.
     * This is created atomically in CallCenterManagementController@settle.
     */
    public function treasuryTransaction(): HasOne
    {
        return $this->hasOne(TreasuryTransaction::class, 'source_id')
                    ->where('source_type', 'settlement');
    }
}
