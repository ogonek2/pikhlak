<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalClientContract extends Model
{
    protected $fillable = [
        'rental_client_id', 'rental_client_vehicle_id', 'car_id', 'crm_external_id', 'contract_number',
        'rent_start', 'rent_end', 'monthly_amount', 'weekly_amount', 'period_weeks',
        'first_payment', 'term_years', 'overpayment_rate', 'total_amount', 'currency',
        'buyout_option', 'status', 'notes', 'calculation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'rent_start' => 'date',
            'rent_end' => 'date',
            'monthly_amount' => 'decimal:2',
            'weekly_amount' => 'decimal:2',
            'first_payment' => 'decimal:2',
            'overpayment_rate' => 'decimal:4',
            'total_amount' => 'decimal:2',
            'buyout_option' => 'boolean',
            'calculation_snapshot' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(RentalClientVehicle::class, 'rental_client_vehicle_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
