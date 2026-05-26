<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRequestLog extends Model
{
    protected $table = 'ai_request_logs';

    protected $fillable = [
        'chat_id', 'model_id', 'prompt_hash', 'status', 'error', 'cost_usd', 'latency_ms',
    ];
}
