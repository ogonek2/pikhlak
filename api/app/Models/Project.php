<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasUuid, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected $fillable = ['uuid', 'name', 'slug', 'settings', 'is_active'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function leadStatuses(): HasMany
    {
        return $this->hasMany(LeadStatus::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->withPivot('role')->withTimestamps();
    }
}
