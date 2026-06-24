<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bot extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'project_id',
        'name',
        'type',
        'telegram_token',
        'webhook_secret',
        'api_key_hash',
        'mode',
        'config',
        'is_active',
    ];

    protected $hidden = ['telegram_token', 'webhook_secret', 'api_key_hash'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isWarming(): bool
    {
        return $this->type === 'warming';
    }

    public function isClient(): bool
    {
        return $this->type === 'client';
    }
}
