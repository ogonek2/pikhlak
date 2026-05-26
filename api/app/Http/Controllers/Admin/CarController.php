<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Project;
use App\Services\Cars\CarAiEnrichmentService;
use App\Services\Cars\CarMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarController extends Controller
{
    public function __construct(
        private readonly CarMediaService $media,
        private readonly CarAiEnrichmentService $aiEnrich,
    ) {}

    public function index(Request $request): View
    {
        $project = $this->project($request);

        $query = Car::query()
            ->with('media')
            ->where('project_id', $project->id);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('q')->toString())) {
            $like = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($like): void {
                $q->whereRaw('LOWER(make) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(model) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(vin) LIKE ?', [$like]);
            });
        }

        $cars = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('admin.cars.index', compact('cars', 'search', 'status'));
    }

    public function show(Request $request, Car $car): View
    {
        $this->ensureProjectCar($request, $car);
        $car->load('media', 'category', 'attributes');

        return view('admin.cars.show', [
            'car' => $car,
            'aiAvailable' => $this->aiEnrich->isAvailable(),
        ]);
    }

    public function create(Request $request): View
    {
        $project = $this->project($request);

        return view('admin.cars.form', [
            'car' => new Car(['status' => 'draft', 'currency' => 'USD', 'project_id' => $project->id]),
            'categories' => $this->categories($project),
            'aiAvailable' => $this->aiEnrich->isAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->project($request);
        $data = $this->validated($request);
        $data['project_id'] = $project->id;
        $data = $this->applyPublishTimestamp($data);

        $car = Car::query()->create($data);
        $count = $this->media->uploadFromRequest($request, $car);

        $flash = "Автомобиль добавлен. Загружено фото: {$count}.";
        if ($request->boolean('ai_enrich', true)) {
            $flash .= ' '.$this->runAiEnrich($car, $project, $request->boolean('ai_overwrite_description'));
        }

        return redirect()
            ->route('admin.cars.show', $car)
            ->with('success', $flash);
    }

    public function edit(Request $request, Car $car): View
    {
        $this->ensureProjectCar($request, $car);
        $car->load('media');

        return view('admin.cars.form', [
            'car' => $car,
            'categories' => $this->categories($car->project),
            'aiAvailable' => $this->aiEnrich->isAvailable(),
        ]);
    }

    public function update(Request $request, Car $car): RedirectResponse
    {
        $this->ensureProjectCar($request, $car);
        $data = $this->validated($request);

        if ($data['status'] === 'published' && ! $car->published_at) {
            $data['published_at'] = now();
        }
        if ($data['status'] !== 'published') {
            $data['published_at'] = $car->published_at;
        }

        $car->update($data);
        $this->media->deleteByIds($car, (array) $request->input('delete_media', []));
        $count = $this->media->uploadFromRequest($request, $car);

        if ($request->filled('media_order')) {
            $this->media->reorder($car, array_filter(explode(',', $request->input('media_order'))));
        }

        $flash = "Сохранено. Новых фото: {$count}.";
        if ($request->boolean('ai_enrich')) {
            $flash .= ' '.$this->runAiEnrich($car, $car->project, $request->boolean('ai_overwrite_description'));
        }

        return redirect()
            ->route('admin.cars.show', $car)
            ->with('success', $flash);
    }

    public function generateAi(Request $request, Car $car): RedirectResponse
    {
        $this->ensureProjectCar($request, $car);
        $project = $this->project($request);
        $result = $this->aiEnrich->enrich($car->fresh(['category']), $project, $request->boolean('overwrite', true));

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(Request $request, Car $car): RedirectResponse
    {
        $this->ensureProjectCar($request, $car);
        $this->media->deleteAll($car);
        $car->delete();

        return redirect()->route('admin.cars.index')->with('success', 'Автомобиль удалён.');
    }

    public function duplicate(Request $request, Car $car): RedirectResponse
    {
        $this->ensureProjectCar($request, $car);
        $car->load('media');

        $copy = $car->replicate(['uuid', 'published_at', 'vin', 'external_id']);
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->save();

        foreach ($car->media as $media) {
            $disk = $media->disk;
            $ext = pathinfo($media->path, PATHINFO_EXTENSION) ?: 'jpg';
            $newPath = "cars/{$copy->id}/".\Illuminate\Support\Str::uuid().'.'.$ext;
            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($media->path)) {
                \Illuminate\Support\Facades\Storage::disk($disk)->copy($media->path, $newPath);
            } else {
                $newPath = $media->path;
            }
            $copy->media()->create([
                'type' => $media->type,
                'path' => $newPath,
                'disk' => $disk,
                'sort' => $media->sort,
                'alt' => $copy->title(),
            ]);
        }

        return redirect()
            ->route('admin.cars.edit', $copy)
            ->with('success', 'Копия создана (фото ссылаются на те же файлы — при необходимости загрузите новые).');
    }

    private function validated(Request $request): array
    {
        $maxKb = (int) config('cars.photos.max_upload_kb', 10240);

        $data = $request->validate([
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:2030'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'status' => ['required', 'in:draft,published,reserved,sold,archived'],
            'category_id' => ['nullable', 'exists:car_categories,id'],
            'vin' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'mileage' => ['nullable', 'string', 'max:50'],
            'engine' => ['nullable', 'string', 'max:100'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:'.$maxKb],
        ]);

        $data['specs'] = array_filter([
            'mileage' => $data['mileage'] ?? null,
            'engine' => $data['engine'] ?? null,
            'transmission' => $data['transmission'] ?? null,
        ]);
        unset($data['mileage'], $data['engine'], $data['transmission'], $data['photos']);

        return $data;
    }

    private function applyPublishTimestamp(array $data): array
    {
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function project(Request $request): Project
    {
        return $request->attributes->get('project');
    }

    private function ensureProjectCar(Request $request, Car $car): void
    {
        $project = $this->project($request);
        if ($car->project_id !== $project->id) {
            abort(404);
        }
    }

    private function categories(Project $project)
    {
        return CarCategory::query()->where('project_id', $project->id)->orderBy('name')->get();
    }

    private function runAiEnrich(Car $car, Project $project, bool $overwriteDescription): string
    {
        $result = $this->aiEnrich->enrich($car->fresh(['category']), $project, $overwriteDescription);

        return $result['message'];
    }
}
