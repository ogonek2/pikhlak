<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficCampaignStat extends Model
{
    protected $fillable = [
        'traffic_channel_id', 'external_id', 'name', 'campaign_type', 'stat_date',
        'impressions', 'clicks', 'leads', 'spend', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'spend' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TrafficChannel::class, 'traffic_channel_id');
    }
}
