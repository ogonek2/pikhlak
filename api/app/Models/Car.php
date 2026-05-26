<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Car extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'project_id', 'category_id', 'vin', 'make', 'model', 'year',
        'price', 'currency', 'status', 'specs', 'import_source',
        'external_id', 'description', 'ai_meta', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_meta' => 'array',
            'specs' => 'array',
            'price' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class, 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(CarMedia::class)->orderBy('sort');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CarAttribute::class);
    }

    public function title(): string
    {
        return trim(implode(' ', array_filter([
            $this->make,
            $this->model,
            $this->year ? (string) $this->year : null,
        ])));
    }

    public function formattedPrice(): string
    {
        if ($this->price === null) {
            return 'цена по запросу';
        }

        return number_format((float) $this->price, 0, '.', ' ').' '.$this->currency;
    }

    public function primaryPhotoUrl(): ?string
    {
        $media = $this->relationLoaded('media')
            ? $this->media->first()
            : $this->media()->orderBy('sort')->first();

        return $media?->publicUrl();
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
