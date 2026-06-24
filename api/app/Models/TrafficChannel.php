<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficChannel extends Model
{
    protected $fillable = [
        'project_id', 'slug', 'name', 'is_active', 'api_connected', 'connection_status',
        'config', 'credentials', 'last_synced_at', 'last_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'api_connected' => 'boolean',
            'config' => 'array',
            'credentials' => 'encrypted:array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(TrafficChannelStat::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(TrafficSyncLog::class);
    }

    public function campaignStats(): HasMany
    {
        return $this->hasMany(TrafficCampaignStat::class);
    }

    public function platformConfig(): ?array
    {
        return config("analytics.platforms.{$this->slug}");
    }

    public function hasCredentials(): bool
    {
        return is_array($this->credentials) && $this->credentials !== [];
    }
}
