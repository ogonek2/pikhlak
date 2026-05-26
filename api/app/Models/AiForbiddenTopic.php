<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiForbiddenTopic extends Model
{
    protected $table = 'ai_forbidden_topics';

    protected $fillable = ['profile_id', 'topic', 'keywords', 'action'];

    protected function casts(): array
    {
        return ['keywords' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiProfile::class, 'profile_id');
    }
}
