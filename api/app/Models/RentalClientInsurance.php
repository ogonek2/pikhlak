<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalClientInsurance extends Model
{
    protected $fillable = [
        'rental_client_id', 'crm_external_id', 'provider', 'policy_number',
        'valid_from', 'valid_until', 'premium_amount', 'coverage_notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'premium_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }
}
