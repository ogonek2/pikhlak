<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSystemPrompt extends Model
{
    protected $table = 'ai_system_prompts';

    protected $fillable = ['profile_id', 'version', 'content', 'is_published', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiProfile::class, 'profile_id');
    }
}
