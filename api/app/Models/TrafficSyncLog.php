<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSyncLog extends Model
{
    protected $fillable = [
        'traffic_channel_id', 'status', 'days_synced', 'rows_upserted',
        'message', 'details', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TrafficChannel::class, 'traffic_channel_id');
    }
}
