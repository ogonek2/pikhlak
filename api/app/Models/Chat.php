<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    protected $fillable = [
        'bot_id', 'telegram_user_id', 'telegram_chat_id', 'type', 'state', 'state_version', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lead(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Lead::class);
    }
}
