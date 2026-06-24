<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalClientPayment extends Model
{
    protected $fillable = [
        'rental_client_id', 'crm_external_id', 'type', 'week_number', 'period_index',
        'amount', 'due_date', 'paid_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }
}
