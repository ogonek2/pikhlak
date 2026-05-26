<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCampaign extends Model
{
    protected $fillable = [
        'project_id', 'code', 'name', 'description', 'utm_defaults', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'utm_defaults' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(ReferralLink::class, 'campaign_id');
    }
}
