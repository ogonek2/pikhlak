<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClientNotificationLog extends Model
{
    protected $fillable = [
        'rental_client_id', 'event_type', 'notifiable_type', 'notifiable_id',
        'offset_days', 'event_date', 'status', 'telegram_message_id',
        'error_message', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
