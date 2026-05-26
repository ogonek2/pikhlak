<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'link_id', 'event_type', 'lead_id', 'meta', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->created_at ??= now();
        });
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'link_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
