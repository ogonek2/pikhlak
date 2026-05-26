<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramUser extends Model
{
    protected $fillable = [
        'bot_id', 'telegram_id', 'username', 'first_name', 'last_name', 'language_code', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
