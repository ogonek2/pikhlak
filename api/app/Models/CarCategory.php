<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarCategory extends Model
{
    protected $fillable = ['project_id', 'parent_id', 'name', 'slug', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'category_id');
    }
}
