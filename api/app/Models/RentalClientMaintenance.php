<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalClientMaintenance extends Model
{
    protected $fillable = [
        'rental_client_id', 'rental_client_vehicle_id', 'crm_external_id', 'type', 'title',
        'scheduled_at', 'completed_at', 'mileage_at', 'cost', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'date',
            'completed_at' => 'date',
            'cost' => 'decimal:2',
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
}
