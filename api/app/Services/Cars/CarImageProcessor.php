<?php

namespace App\Services\Cars;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarImageProcessor
{
    public function store(UploadedFile $file, int $carId): string
    {
        $directory = "cars/{$carId}";
        $disk = config('cars.photos.disk', 'public');
        Storage::disk($disk)->makeDirectory($directory);

        $contents = file_get_contents($file->getRealPath());
        $sizeKb = (int) ceil(strlen($contents) / 1024);
        $shouldCompress = $sizeKb >= (int) config('cars.photos.compress_above_kb', 400);

        if ($shouldCompress && $this->canProcessImages()) {
            $processed = $this->compress($file->getRealPath());
            if ($processed !== null) {
                $filename = Str::uuid().'.jpg';
                $path = "{$directory}/{$filename}";
                Storage::disk($disk)->put($path, $processed);

                return $path;
            }
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid().'.'.$ext;
        $path = "{$directory}/{$filename}";
        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    public function canProcessImages(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring');
    }

    private function compress(string $sourcePath): ?string
    {
        $binary = @file_get_contents($sourcePath);
        if ($binary === false) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxWidth = (int) config('cars.photos.max_width', 1600);

        if ($width > $maxWidth) {
            $newHeight = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagejpeg($image, null, (int) config('cars.photos.jpeg_quality', 82));
        $output = ob_get_clean();
        imagedestroy($image);

        return $output !== false ? $output : null;
    }
}
