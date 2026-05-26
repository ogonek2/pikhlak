<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadScore extends Model
{
    protected $fillable = ['lead_id', 'score', 'factors', 'calculated_at'];

    protected function casts(): array
    {
        return [
            'factors' => 'array',
            'calculated_at' => 'datetime',
        ];
    }
}
