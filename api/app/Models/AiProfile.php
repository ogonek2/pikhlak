<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProfile extends Model
{
    protected $table = 'ai_profiles';

    protected $fillable = [
        'project_id', 'bot_id', 'name', 'personality', 'temperature',
        'max_tokens', 'model_id', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'personality' => 'array',
            'temperature' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(AiSystemPrompt::class, 'profile_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(AiResponseTemplate::class, 'profile_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    public function promptRules(): HasMany
    {
        return $this->hasMany(AiPromptRule::class, 'profile_id');
    }

    public function allowedTopics(): HasMany
    {
        return $this->hasMany(AiAllowedTopic::class, 'profile_id');
    }

    public function forbiddenTopics(): HasMany
    {
        return $this->hasMany(AiForbiddenTopic::class, 'profile_id');
    }

    public function warmingScenarios(): HasMany
    {
        return $this->hasMany(AiWarmingScenario::class, 'profile_id');
    }
}
