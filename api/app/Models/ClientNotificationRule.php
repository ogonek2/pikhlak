<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNotificationRule extends Model
{
    protected $fillable = [
        'project_id', 'event_type', 'offset_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'offset_days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
