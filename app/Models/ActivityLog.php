<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'event',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'causer_id',
        'causer_role',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeWithinDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    public function scopeWithinTimeRange(Builder $query, ?string $timeFrom, ?string $timeTo): Builder
    {
        if ($timeFrom) {
            $query->whereTime('created_at', '>=', $timeFrom);
        }
        if ($timeTo) {
            $query->whereTime('created_at', '<=', $timeTo);
        }
        return $query;
    }

    public function scopeForCauser(Builder $query, ?int $causerId): Builder
    {
        if ($causerId) {
            $query->where('causer_id', $causerId);
        }
        return $query;
    }

    public function scopeForOrderNumber(Builder $query, ?string $orderNumber): Builder
    {
        if ($orderNumber) {
            $query->where(function ($q) use ($orderNumber) {
                $q->where('subject_label', 'like', "%{$orderNumber}%")
                  ->orWhereJsonContains('properties->order_number', $orderNumber);
            });
        }
        return $query;
    }

    public function scopeSearchClient(Builder $query, ?string $search): Builder
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_label', 'like', "%{$search}%")
                  ->orWhereJsonContains('properties->client_code', $search)
                  ->orWhere(function ($q2) use ($search) {
                      $q2->whereJsonContains('properties->client_name', $search);
                  });
            });
        }
        return $query;
    }

    // ── Static Logger ───────────────────────────────────────────────

    /**
     * Central logging method used across all controllers.
     *
     * @param string      $event        Machine-readable key (e.g. 'order.created')
     * @param string      $description  Human-readable Arabic description
     * @param string|null $subjectType  'order'|'client'|'user'|'shop'|'treasury'|null
     * @param int|null    $subjectId
     * @param string|null $subjectLabel Readable label (order number, client name …)
     * @param array       $properties   Extra payload
     * @param int|null    $causerId     Defaults to auth()->id()
     */
    public static function log(
        string  $event,
        string  $description,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        ?string $subjectLabel = null,
        array   $properties  = [],
        ?int    $causerId    = null
    ): static {
        $user = $causerId
            ? User::find($causerId)
            : auth()->user();

        return static::create([
            'event'         => $event,
            'description'   => $description,
            'subject_type'  => $subjectType,
            'subject_id'    => $subjectId,
            'subject_label' => $subjectLabel,
            'causer_id'     => $user?->id,
            'causer_role'   => $user?->role,
            'properties'    => $properties ?: null,
        ]);
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getEventIconAttribute(): string
    {
        return match (true) {
            str_starts_with($this->event, 'order.')        => '📦',
            str_starts_with($this->event, 'client.')       => '👤',
            str_starts_with($this->event, 'user.')         => '🧑‍💼',
            str_starts_with($this->event, 'shop.')         => '🏪',
            str_starts_with($this->event, 'treasury.')     => '💰',
            str_starts_with($this->event, 'shift.')        => '🕐',
            str_starts_with($this->event, 'settlement.')   => '🤝',
            default                                        => '📋',
        };
    }

    public function getCauserRoleLabelAttribute(): string
    {
        return match ($this->causer_role) {
            'admin'            => 'أدمن',
            'callcenter'       => 'كول سنتر',
            'delivery'         => 'مندوب',
            'reserve_delivery' => 'مندوب احتياطي',
            default            => $this->causer_role ?? '—',
        };
    }

    public function getCauserRoleBadgeAttribute(): string
    {
        return match ($this->causer_role) {
            'admin'            => 'badge-red',
            'callcenter'       => 'badge-blue',
            'delivery'         => 'badge-green',
            'reserve_delivery' => 'badge-yellow',
            default            => 'badge-gray',
        };
    }
}
