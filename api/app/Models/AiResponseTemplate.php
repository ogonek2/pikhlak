<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResponseTemplate extends Model
{
    protected $table = 'ai_response_templates';

    protected $fillable = ['profile_id', 'code', 'template', 'locale'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AiProfile::class, 'profile_id');
    }
}
