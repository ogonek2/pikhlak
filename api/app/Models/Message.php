<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'chat_id', 'direction', 'telegram_message_id', 'type', 'body', 'payload', 'ai_message_id',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
