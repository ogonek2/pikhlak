<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientManagerRequest extends Model
{
    protected $fillable = [
        'project_id',
        'rental_client_id',
        'bot_id',
        'telegram_user_id',
        'telegram_chat_id',
        'source',
        'client_message',
        'status',
        'admin_notes',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'in_progress'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'in_progress' => 'В работе',
            'resolved' => 'Обработан',
            'cancelled' => 'Отменён',
            default => 'Ожидает',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'button' => 'Кнопка «Менеджер»',
            'text' => 'Сообщение в чате',
            default => $this->source,
        };
    }
}
