<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptRule extends Model
{
    protected $table = 'ai_prompt_rules';

    protected $fillable = ['profile_id', 'name', 'type', 'priority', 'condition', 'instruction', 'is_active'];

    protected function casts(): array
    {
        return ['condition' => 'array', 'is_active' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiProfile::class, 'profile_id');
    }

    public function isAlwaysOn(): bool
    {
        return (bool) ($this->condition['always'] ?? false);
    }

    public function triggerKeywords(): array
    {
        $kw = $this->condition['keywords'] ?? [];

        return is_array($kw) ? $kw : [];
    }
}
