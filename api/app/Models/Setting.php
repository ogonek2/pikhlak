<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = ['project_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function getValue(int $projectId, string $key, mixed $default = null): mixed
    {
        $setting = static::query()
            ->where('project_id', $projectId)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    public static function setValue(int $projectId, string $key, mixed $value): self
    {
        return static::query()->updateOrCreate(
            ['project_id' => $projectId, 'key' => $key],
            ['value' => $value]
        );
    }
}
