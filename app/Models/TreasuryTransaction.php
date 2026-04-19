<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransaction extends Model
{
    protected $fillable = [
        'type',
        'source_type',
        'source_id',
        'amount',
        'by_whom',
        'note',
        'recorded_by',
        'transaction_date',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    /**
     * The admin who recorded this transaction.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * The parent settlement record (only set when source_type = 'settlement').
     * Used by the "View Details" modal to show the originating CC settlement.
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CallcenterSettlement::class, 'source_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Query Scopes
    // ──────────────────────────────────────────────────────────────

    /** Filter to income rows only. */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    /** Filter to expense rows only. */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    /** Filter to settlement rows only. */
    public function scopeSettlement(Builder $query): Builder
    {
        return $query->where('type', 'settlement');
    }

    /** Filter to dain (expense) rows only. */
    public function scopeDain(Builder $query): Builder
    {
        return $query->where('type', 'dain');
    }

    /** Filter to discount rows only. */
    public function scopeDiscount(Builder $query): Builder
    {
        return $query->where('type', 'discount');
    }

    /**
     * All rows that contribute positively to the balance
     * (income + settlement).
     */
    public function scopeCredit(Builder $query): Builder
    {
        return $query->whereIn('type', ['income', 'settlement']);
    }

    /**
     * Filter by a date range on transaction_date.
     * Both parameters are optional — pass null to leave that side open.
     *
     * Usage:
     *   TreasuryTransaction::withinDateRange('2024-01-01', '2024-01-31')
     *   TreasuryTransaction::withinDateRange('2024-01-01', null)   // from date only
     *   TreasuryTransaction::withinDateRange(null, null)           // no filter (all-time)
     */
    public function scopeWithinDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('transaction_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('transaction_date', '<=', $to);
        }

        return $query;
    }

    /**
     * Filter by transaction type. Accepts 'income', 'expense', 'settlement',
     * or null/'all' to skip the filter.
     */
    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if ($type && in_array($type, ['income', 'expense', 'settlement', 'dain', 'discount'])) {
            $query->where('type', $type);
        }

        return $query;
    }

    // ──────────────────────────────────────────────────────────────
    // Static Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Calculate the three KPI values for the Treasury dashboard.
     *
     * Accepts optional date range to make the stats react to filters.
     * Note: balance is ALWAYS calculated on the full dataset regardless
     * of the type filter — it represents the real treasury state.
     *
     * @param string|null $from  Start of date range (Y-m-d)
     * @param string|null $to    End of date range (Y-m-d)
     * @return array{total_income: string, total_expense: string, balance: string}
     */
    public static function calculateKpis(?string $from = null, ?string $to = null): array
    {
        // Single query — group by type, sum amounts
        $rows = static::query()
            ->withinDateRange($from, $to)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $totalIncome     = (float) ($rows['income']     ?? 0);
        $totalExpense    = (float) ($rows['expense']    ?? 0);
        $totalSettlement = (float) ($rows['settlement'] ?? 0);
        $totalDain       = (float) ($rows['dain']       ?? 0);
        $totalDiscount   = (float) ($rows['discount']   ?? 0);

        // Dain counts as expense (مصرف) — per user feedback
        // Discount is NOT added to balance calculation for now per user request
        $adjustedExpenses = $totalExpense + $totalDain;

        return [
            'total_income'   => number_format($totalIncome, 2),
            'total_expense'  => number_format($adjustedExpenses, 2),
            'total_dain'     => number_format($totalDain, 2),
            'balance'        => number_format(
                ($totalIncome + $totalSettlement) - $adjustedExpenses,
                2
            ),
        ];
    }

    /**
     * Convenience factory for creating a manual treasury entry.
     * Called from TreasuryController@addIncome and @addExpense.
     */
    public static function createManual(
        string $type,      // 'income' | 'expense'
        string $byWhom,
        float  $amount,
        ?string $note,
        int    $recordedBy,
        ?string $transactionDate = null
    ): static {
        return static::create([
            'type'             => $type,
            'source_type'      => 'manual',
            'source_id'        => null,
            'amount'           => $amount,
            'by_whom'          => $byWhom,
            'note'             => $note,
            'recorded_by'      => $recordedBy,
            'transaction_date' => $transactionDate ?? now()->toDateString(),
        ]);
    }

    /**
     * Convenience factory for creating a settlement ledger entry.
     * Called from CallCenterManagementController@settle ONLY.
     * Always pass the $settlement model AFTER it has been saved so
     * we can link source_id correctly.
     */
    public static function createFromSettlement(
        CallcenterSettlement $settlement
    ): static {
        // Combine agent name + admin note for the unified note field
        $agentName = $settlement->callcenter->name ?? 'مجهول';
        $adminNote = $settlement->note;

        $combinedNote = $adminNote
            ? "{$agentName} — {$adminNote}"
            : $agentName;

        return static::create([
            'type'             => 'settlement',
            'source_type'      => 'settlement',
            'source_id'        => $settlement->id,
            'amount'           => $settlement->amount,
            'by_whom'          => $agentName,
            'note'             => $combinedNote,
            'recorded_by'      => $settlement->settled_by,
            'transaction_date' => $settlement->settled_at->toDateString(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────

    /**
     * Human-readable Arabic label for the transaction type.
     * Used in Blade views and PDF exports.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'income'     => 'إيراد',
            'expense'    => 'مصروف',
            'settlement' => 'تسوية',
            'dain'       => 'صرف مديونية',
            'discount'   => 'خصم',
            default      => $this->type,
        };
    }

    /**
     * CSS class suffix for the type badge in the ledger table.
     * income → green, expense → red, settlement → yellow (brand)
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'income'     => 'success',
            'expense'    => 'danger',
            'settlement' => 'warning',
            'dain'       => 'indigo', // specific color for dain
            'discount'   => 'danger', // requested red color (danger)
            default      => 'secondary',
        };
    }
}
