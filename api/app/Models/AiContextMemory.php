<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiContextMemory extends Model
{
    protected $table = 'ai_context_memories';

    protected $fillable = ['chat_id', 'summary', 'tokens_estimate'];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
