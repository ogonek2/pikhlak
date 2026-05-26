<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralAttribution extends Model
{
    protected $fillable = [
        'lead_id', 'link_id', 'first_touch_at', 'last_touch_at',
    ];

    protected function casts(): array
    {
        return [
            'first_touch_at' => 'datetime',
            'last_touch_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'link_id');
    }
}
