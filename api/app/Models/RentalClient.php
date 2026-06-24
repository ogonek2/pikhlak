<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalClient extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'project_id', 'bot_id', 'uuid', 'crm_external_id', 'crm_synced_at', 'link_token',
        'full_name', 'email', 'telegram_user_id', 'telegram_chat_id',
        'status', 'notifications_enabled', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'notifications_enabled' => 'boolean',
            'crm_synced_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(RentalClientPhone::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(RentalClientVehicle::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(RentalClientContract::class);
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(RentalClientInsurance::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(RentalClientMaintenance::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentalClientPayment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RentalClientInvoice::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(ClientNotificationLog::class);
    }

    public function currentVehicle(): ?RentalClientVehicle
    {
        return $this->vehicles()->where('is_current', true)->first()
            ?? $this->vehicles()->latest()->first();
    }

    public function activeContract(): ?RentalClientContract
    {
        return $this->contracts()->where('status', 'active')->latest('rent_start')->first();
    }

    public function isTelegramLinked(): bool
    {
        return $this->telegram_chat_id !== null || $this->telegram_user_id !== null;
    }

    public function resolveTelegramChatId(): ?int
    {
        if ($this->telegram_chat_id) {
            return (int) $this->telegram_chat_id;
        }

        if ($this->telegram_user_id) {
            return (int) $this->telegram_user_id;
        }

        return null;
    }
}
