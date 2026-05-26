<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CarMedia extends Model
{
    protected $table = 'car_media';

    protected $fillable = ['car_id', 'type', 'path', 'disk', 'sort', 'alt'];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function publicUrl(): string
    {
        return url(Storage::disk($this->disk)->url($this->path));
    }

    /** Путь на диске для отправки фото ботом (Telegram не открывает localhost URL). */
    public function absolutePath(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $path = Storage::disk($this->disk)->path($this->path);

        return is_file($path) ? $path : null;
    }

    public function toBotPayload(?string $caption = null): array
    {
        return [
            'type' => 'photo',
            'url' => $this->publicUrl(),
            'file_path' => $this->absolutePath(),
            'caption' => $caption,
        ];
    }
}
