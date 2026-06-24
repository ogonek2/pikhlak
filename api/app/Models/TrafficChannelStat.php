<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficChannelStat extends Model
{
    protected $fillable = [
        'traffic_channel_id', 'stat_date', 'impressions', 'clicks', 'leads',
        'views', 'applications', 'subscribers', 'likes', 'comments', 'source_type',
        'spend', 'revenue', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'spend' => 'decimal:2',
            'revenue' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TrafficChannel::class, 'traffic_channel_id');
    }
}
