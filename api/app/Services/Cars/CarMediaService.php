<?php

namespace App\Services\Cars;

use App\Models\Car;
use App\Models\CarMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarMediaService
{
    public function __construct(private readonly CarImageProcessor $images) {}

    public function uploadFromRequest(Request $request, Car $car): int
    {
        if (! $request->hasFile('photos')) {
            return 0;
        }

        $max = (int) config('cars.photos.max_photos_per_car', 15);
        $current = $car->media()->count();
        $sort = (int) $car->media()->max('sort');
        $uploaded = 0;

        foreach ($request->file('photos') as $file) {
            if ($current + $uploaded >= $max) {
                break;
            }

            $path = $this->images->store($file, $car->id);
            $sort++;

            CarMedia::query()->create([
                'car_id' => $car->id,
                'type' => 'image',
                'path' => $path,
                'disk' => config('cars.photos.disk', 'public'),
                'sort' => $sort,
                'alt' => $car->title(),
            ]);
            $uploaded++;
        }

        return $uploaded;
    }

    public function deleteByIds(Car $car, array $ids): void
    {
        foreach ($ids as $mediaId) {
            $media = CarMedia::query()->where('car_id', $car->id)->where('id', $mediaId)->first();
            if ($media) {
                $this->deleteFile($media);
                $media->delete();
            }
        }
    }

    public function deleteAll(Car $car): void
    {
        foreach ($car->media as $media) {
            $this->deleteFile($media);
            $media->delete();
        }
    }

    public function reorder(Car $car, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            CarMedia::query()
                ->where('car_id', $car->id)
                ->where('id', $id)
                ->update(['sort' => $index + 1]);
        }
    }

    private function deleteFile(CarMedia $media): void
    {
        if ($media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }
    }
}
