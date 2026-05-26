<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasUuid, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected $fillable = [
        'project_id', 'uuid', 'chat_id', 'telegram_user_id', 'status_id',
        'assigned_manager_id', 'warming_score', 'source', 'referral_link_id', 'car_interest_id', 'metadata',
        'last_contacted_at', 'operator_requested_at', 'operator_handled_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_contacted_at' => 'datetime',
            'operator_requested_at' => 'datetime',
            'operator_handled_at' => 'datetime',
        ];
    }

    public function needsOperator(): bool
    {
        return $this->operator_requested_at !== null && $this->operator_handled_at === null;
    }

    public function scopeNeedsOperator($query)
    {
        return $query->whereNotNull('operator_requested_at')->whereNull('operator_handled_at');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class, 'status_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'referral_link_id');
    }
}
