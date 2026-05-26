<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralLink extends Model
{
    protected $fillable = [
        'project_id', 'bot_id', 'campaign_id', 'code', 'name', 'type', 'channel',
        'car_id', 'partner_name', 'partner_contact', 'partner_commission_percent',
        'telegram_user_id', 'manager_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'settings', 'description', 'landing_message',
        'expires_at', 'max_starts',
        'clicks_count', 'starts_count', 'leads_count', 'conversions_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'partner_commission_percent' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ReferralCampaign::class, 'campaign_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReferralEvent::class, 'link_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'referral_link_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isOverLimit(): bool
    {
        return $this->max_starts && $this->starts_count >= $this->max_starts;
    }

    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isOverLimit();
    }

    public function channelLabel(): string
    {
        if (! $this->channel) {
            return '—';
        }

        return config('referrals.channels.'.$this->channel, $this->channel);
    }

    public function typeLabel(): string
    {
        return config('referrals.types.'.$this->type, $this->type);
    }

    public function conversionRate(): float
    {
        if ($this->starts_count < 1) {
            return 0;
        }

        return round(($this->leads_count / $this->starts_count) * 100, 1);
    }
}
