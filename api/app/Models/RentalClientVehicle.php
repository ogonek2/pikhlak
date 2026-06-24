<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalClientVehicle extends Model
{
    protected $fillable = [
        'rental_client_id', 'car_id', 'make', 'model', 'year', 'color',
        'plate_number', 'vin', 'mileage', 'is_current',
    ];

    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(RentalClientContract::class);
    }

    public function catalogCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function title(): string
    {
        return trim("{$this->make} {$this->model}".($this->year ? " {$this->year}" : ''));
    }
}
