<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFaqItem extends Model
{
    protected $table = 'ai_faq_items';

    protected $fillable = ['project_id', 'question', 'answer', 'locale', 'tags', 'is_active'];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
