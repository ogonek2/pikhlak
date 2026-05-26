<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = ['provider', 'model_name', 'config', 'is_active'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean'];
    }
}
