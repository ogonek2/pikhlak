<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiWarmingScenario extends Model
{
    protected $table = 'ai_warming_scenarios';

    protected $fillable = ['profile_id', 'name', 'steps', 'triggers', 'is_active'];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'triggers' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiProfile::class, 'profile_id');
    }
}
