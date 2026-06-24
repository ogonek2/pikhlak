<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalClientInvoice extends Model
{
    protected $fillable = [
        'rental_client_id', 'rental_client_payment_id', 'invoice_number',
        'amount', 'currency', 'pdf_path', 'qr_path', 'payment_url',
        'status', 'issued_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RentalClient::class, 'rental_client_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RentalClientPayment::class, 'rental_client_payment_id');
    }
}
