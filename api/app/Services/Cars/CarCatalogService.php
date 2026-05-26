<?php

namespace App\Services\Cars;

use App\Models\Car;
use App\Models\Project;
use Illuminate\Support\Collection;

class CarCatalogService
{
    public function publishedForProject(int $projectId, ?string $search = null, int $limit = 12): Collection
    {
        $query = Car::query()
            ->with(['media', 'category'])
            ->where('project_id', $projectId)
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($search && $this->isSpecificCarSearch($search)) {
            $lower = mb_strtolower($search);
            $tokens = preg_split('/[\s,.;:!?\-]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) >= 2));

            $query->where(function ($q) use ($lower, $tokens): void {
                $q->whereRaw('LOWER(make) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(model) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(CONCAT(make, " ", model)) LIKE ?', ["%{$lower}%"]);

                foreach ($tokens as $token) {
                    $q->orWhereRaw('LOWER(make) LIKE ?', ["%{$token}%"])
                        ->orWhereRaw('LOWER(model) LIKE ?', ["%{$token}%"]);
                }
            });
        }

        return $query->limit($limit)->get();
    }

    public function isCatalogInquiry(string $text): bool
    {
        if ($this->isPhotoRequest($text)) {
            return false;
        }

        $lower = mb_strtolower($text);

        foreach ([
            'каталог', 'наличии', 'наличие', 'в наличии', 'какие машин', 'какие авто',
            'что есть', 'есть ли', 'список', 'у вас есть', 'имеется',
            'сколько машин', 'сколько авто', 'покажи каталог', 'покажи список',
        ] as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        if (preg_match('/\bпокажи\b/u', $lower) && ! preg_match('/\b(фото|фотограф|слайд|галере)\w*/u', $lower)) {
            if (preg_match('/\b(каталог|наличии|авто|машин|список|что есть)\b/u', $lower)) {
                return true;
            }
        }

        return false;
    }

    private function isSpecificCarSearch(string $search): bool
    {
        if ($this->isCatalogInquiry($search)) {
            return false;
        }

        return mb_strlen(trim($search)) <= 35;
    }

    public function formatTelegramList(Collection $cars): string
    {
        if ($cars->isEmpty()) {
            return '🚘 Сейчас в каталоге нет опубликованных авто. Опишите марку и бюджет — подберём под заказ.';
        }

        $lines = ['🚘 <b>Авто в наличии</b> ('.$cars->count().' шт.):', ''];
        foreach ($cars as $car) {
            $lines[] = sprintf(
                '• <b>%s</b> — %s (ID:%d)',
                $car->title(),
                $car->formattedPrice(),
                $car->id
            );
        }
        $lines[] = '';
        $lines[] = 'Напишите ID или марку — расскажу подробнее и помогу с доставкой.';

        return implode("\n", $lines);
    }

    public function formatForAiContext(Collection $cars): string
    {
        if ($cars->isEmpty()) {
            return 'Каталог авто в наличии: сейчас нет опубликованных позиций. Предложи оставить заявку на подбор.';
        }

        $lines = ['Каталог авто в наличии (используй ТОЛЬКО эти данные, не выдумывай цены и характеристики):'];

        foreach ($cars as $car) {
            $specs = collect($car->specs ?? [])
                ->map(fn ($v, $k) => "{$k}: {$v}")
                ->implode(', ');

            $aiHint = '';
            if (! empty($car->ai_meta['bot_context'])) {
                $aiHint = ' | AI: '.mb_substr(strip_tags($car->ai_meta['bot_context']), 0, 100);
            }

            $lines[] = sprintf(
                '- ID:%d | %s | %s | статус:%s%s%s%s',
                $car->id,
                $car->title(),
                $car->formattedPrice(),
                $car->status,
                $specs ? " | {$specs}" : '',
                $car->description ? ' | '.mb_substr(strip_tags($car->description), 0, 120) : '',
                $aiHint
            );
        }

        return implode("\n", $lines);
    }

    public function findInterestedCar(int $projectId, string $text): ?Car
    {
        if ($this->isCatalogInquiry($text) && ! $this->isPhotoRequest($text)) {
            return null;
        }

        $byId = $this->findByIdInText($projectId, $text);
        if ($byId) {
            return $byId;
        }

        $minScore = $this->isPhotoRequest($text) ? 4 : 6;
        $ranked = $this->rankCars($projectId, $text);

        $hit = $ranked->first(fn (array $row) => $row['score'] >= $minScore);

        return $hit['car'] ?? null;
    }

    public function findCarCandidates(int $projectId, string $text, int $limit = 5): Collection
    {
        $minScore = $this->isPhotoRequest($text) ? 3 : 4;

        return $this->rankCars($projectId, $text)
            ->filter(fn ($row) => $row['score'] >= $minScore)
            ->take($limit)
            ->pluck('car');
    }

    /**
     * @return Collection<int, array{car: Car, score: int}>
     */
    private function rankCars(int $projectId, string $text): Collection
    {
        $normalized = $this->normalizeSearchText($text);
        $tokens = $this->searchTokens($normalized);
        $cars = $this->publishedForProject($projectId, null, 50)->loadMissing('media');

        return $cars
            ->map(fn (Car $car) => ['car' => $car, 'score' => $this->scoreCarMatch($car, $normalized, $tokens)])
            ->filter(fn ($row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();
    }

    private function scoreCarMatch(Car $car, string $normalized, array $tokens): int
    {
        $make = mb_strtolower($car->make);
        $model = mb_strtolower($car->model);
        $title = "{$make} {$model}";
        $score = 0;

        if ($normalized !== '' && str_contains($normalized, $title)) {
            $score += 12;
        }
        if ($normalized !== '' && str_contains($normalized, $make)) {
            $score += 4;
        }
        if ($normalized !== '' && str_contains($normalized, $model)) {
            $score += 7;
        }
        if ($car->year && $normalized !== '' && str_contains($normalized, (string) $car->year)) {
            $score += 3;
        }

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }
            if ($token === $make || $token === $model) {
                $score += 8;
            }
            if (str_contains($make, $token) || str_contains($token, $make)) {
                $score += 5;
            }
            if (str_contains($model, $token) || str_contains($token, $model)) {
                $score += 7;
            }
            if ($this->fuzzyMatch($token, $model) || $this->fuzzyMatch($token, $make)) {
                $score += 6;
            }
            if (str_contains($title, $token)) {
                $score += 4;
            }
        }

        $aiMeta = $car->ai_meta ?? [];
        foreach ((array) ($aiMeta['search_aliases'] ?? []) as $alias) {
            $alias = mb_strtolower(trim($alias));
            if ($alias !== '' && ($normalized !== '' && str_contains($normalized, $alias))) {
                $score += 8;
            }
            foreach ($tokens as $token) {
                if ($alias !== '' && ($token === $alias || $this->fuzzyMatch($token, $alias))) {
                    $score += 7;
                }
            }
        }
        foreach ((array) ($aiMeta['keywords'] ?? []) as $keyword) {
            $keyword = mb_strtolower(trim($keyword));
            if ($keyword !== '' && $normalized !== '' && str_contains($normalized, $keyword)) {
                $score += 3;
            }
        }

        return $score;
    }

    private function fuzzyMatch(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }
        if (str_contains($b, $a) || str_contains($a, $b)) {
            return true;
        }
        if (mb_strlen($a) >= 4 && mb_strlen($b) >= 4) {
            $stemA = mb_substr($a, 0, 4);
            $stemB = mb_substr($b, 0, 4);
            if ($stemA === $stemB) {
                return true;
            }
        }

        return levenshtein($a, $b) <= 2;
    }

    private function searchTokens(string $normalized): array
    {
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/[\s,.;:!?\-]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($parts, fn ($t) => mb_strlen($t) >= 2));
    }

    private function findByIdInText(int $projectId, string $text): ?Car
    {
        $lower = mb_strtolower($text);
        if (! preg_match('/\b(?:id|№|#)\s*(\d+)\b/u', $lower, $m)) {
            return null;
        }

        return Car::query()
            ->with('media')
            ->where('project_id', $projectId)
            ->published()
            ->where('id', (int) $m[1])
            ->first();
    }

    public function normalizeSearchText(string $text): string
    {
        $lower = mb_strtolower(trim($text));

        $stopwords = config('cars.search_stopwords', []);
        foreach ($stopwords as $word) {
            $lower = preg_replace('/\b'.preg_quote($word, '/').'\b/u', ' ', $lower) ?? $lower;
        }

        $aliases = config('cars.search_aliases', []);
        foreach ($aliases as $from => $to) {
            $lower = preg_replace('/\b'.preg_quote($from, '/').'\b/u', $to, $lower) ?? $lower;
        }

        return trim(preg_replace('/\s+/u', ' ', $lower) ?? $lower);
    }

    public function isCarDetailInquiry(string $text): bool
    {
        if ($this->isPhotoRequest($text) || $this->isCatalogInquiry($text)) {
            return false;
        }

        $lower = mb_strtolower($text);

        foreach ([
            'цена', 'стоимость', 'сколько', 'подробнее', 'подробней', 'расскажи', 'рассказать',
            'характерист', 'комплектац', 'пробег', 'двигатель', 'объем', 'объём',
            'доставк', 'растамож', 'интересует', 'про машину', 'про авто',
            'эта машина', 'этот авто', 'эту машину', 'что за', 'какая машина',
        ] as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    public function formatCarDetail(Car $car): string
    {
        $specs = collect($car->specs ?? [])
            ->map(fn ($v, $k) => "{$k}: {$v}")
            ->implode(', ');

        $lines = [
            "🚗 <b>{$car->title()}</b>",
            "💰 {$car->formattedPrice()}",
            "📌 ID: {$car->id} · статус: {$car->status}",
        ];

        if ($specs) {
            $lines[] = "⚙️ {$specs}";
        }
        if ($car->description) {
            $lines[] = mb_substr(strip_tags($car->description), 0, 400);
        }
        $lines[] = '';
        $lines[] = 'Могу рассчитать доставку — напишите город и срок покупки.';

        return implode("\n", $lines);
    }

    public function isPhotoRequest(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        if (preg_match('/\b(фото|фотограф|фотк|слайд|галере|картинк|снимк|снимок)\w*/u', $lower)) {
            return true;
        }

        if (preg_match('/\b(покажи|показать|показ|скинь|пришли|отправь|дай|глянуть|посмотреть)\b/u', $lower)
            && preg_match('/\b(фото|машин|авто|модель|её|его|эту|этот|это|ней|нем|сонат|к5|bmw|kia)\w*/u', $lower)) {
            return true;
        }

        if (preg_match('/^(фото|photo|pics?)\s+\S+/u', $lower)) {
            return true;
        }

        return false;
    }

    public function resolveCarForPhotoRequest(int $projectId, string $userText, ?\App\Models\Lead $lead = null): ?Car
    {
        if (! $this->isPhotoRequest($userText)) {
            return null;
        }

        $lower = mb_strtolower($userText);
        if (preg_match('/\b(эту|этот|это|её|его|ней|нем|эта)\b/u', $lower) && $lead?->car_interest_id) {
            $car = Car::query()
                ->with('media')
                ->where('project_id', $projectId)
                ->published()
                ->find($lead->car_interest_id);
            if ($car) {
                return $car;
            }
        }

        return $this->findInterestedCar($projectId, $userText);
    }

    /** Альбом (слайды) — только по явному запросу фото. */
    public function buildCarPhotoAlbumActions(int $chatId, Car $car, ?string $caption = null): array
    {
        $caption ??= $car->ai_meta['photo_caption'] ?? null;
        $caption ??= $this->formatCarDetail($car);
        $photos = $car->media
            ->filter(fn ($m) => $m->absolutePath())
            ->take(10);

        if ($photos->isEmpty()) {
            return [
                ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
                [
                    'type' => 'send_message',
                    'chat_id' => $chatId,
                    'text' => $caption."\n\n📷 Фото для этого авто пока не загружены в каталог.",
                    'parse_mode' => 'HTML',
                ],
            ];
        }

        $media = $photos->values()->map(function ($m, $index) use ($caption) {
            return $m->toBotPayload($index === 0 ? $caption : null);
        })->all();

        return [
            ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
            [
                'type' => 'send_media_group',
                'chat_id' => $chatId,
                'media' => $media,
            ],
        ];
    }

    public function buildTelegramCatalogActions(Project $project, int $chatId, ?string $search = null): array
    {
        $cars = $this->publishedForProject($project->id, $search, 15);

        if ($cars->isEmpty()) {
            return [
                [
                    'type' => 'send_message',
                    'chat_id' => $chatId,
                    'text' => '🚘 Сейчас в каталоге нет опубликованных авто. Опишите желаемую марку и бюджет — AI-консультант подберёт варианты.',
                    'parse_mode' => 'HTML',
                ],
            ];
        }

        return [
            [
                'type' => 'typing',
                'chat_id' => $chatId,
                'duration' => 1,
            ],
            [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => $this->formatTelegramList($cars),
                'parse_mode' => 'HTML',
            ],
            [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => 'Чтобы увидеть <b>фото</b> — напишите, например: «Покажи фото Kia K5» или «Фото ID:2».',
                'parse_mode' => 'HTML',
            ],
        ];
    }
}
