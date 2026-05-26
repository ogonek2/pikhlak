<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRoute extends Model
{
    protected $fillable = [
        'project_id', 'name', 'slug', 'intent_keywords', 'model_id', 'profile_id',
        'pipeline', 'data_sources', 'extra_instruction', 'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'intent_keywords' => 'array',
            'pipeline' => 'array',
            'data_sources' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiProfile::class, 'profile_id');
    }
}
