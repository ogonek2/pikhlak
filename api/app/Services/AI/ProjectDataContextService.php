<?php

namespace App\Services\AI;

use App\Models\AiFaqItem;
use App\Models\Car;
use App\Models\Lead;
use App\Services\Cars\CarCatalogService;

class ProjectDataContextService
{
    public function __construct(private readonly CarCatalogService $cars) {}

    public function build(int $projectId, array $sources, string $userText, ?Lead $lead = null): string
    {
        $sections = [];

        if (in_array('cars', $sources, true)) {
            $search = $this->cars->isCatalogInquiry($userText) ? null : $userText;
            $inventory = $this->cars->publishedForProject($projectId, $search, 6);
            $sections[] = $this->cars->formatForAiContext($inventory);
        }

        if (in_array('faq', $sources, true)) {
            $items = AiFaqItem::query()
                ->where('project_id', $projectId)
                ->where('is_active', true)
                ->limit(8)
                ->get();

            if ($items->isNotEmpty()) {
                $lines = ['FAQ (используй при совпадении вопроса):'];
                foreach ($items as $item) {
                    $lines[] = 'Q: '.$item->question.' → A: '.mb_substr($item->answer, 0, 200);
                }
                $sections[] = implode("\n", $lines);
            }
        }

        if (in_array('leads', $sources, true) && $lead) {
            $sections[] = sprintf(
                '[Внутренне] Статус заявки: %s. Авто в фокусе: %s',
                $lead->status?->name ?? 'новый',
                $lead->car_interest_id ? 'каталог #'.$lead->car_interest_id : 'не выбран'
            );

            $focusId = $lead->car_interest_id;
            if (preg_match('/\b(эту|этот|это|её|его|ней|нем|эта)\b/u', mb_strtolower($userText)) && $focusId) {
                $focusCar = Car::query()->where('project_id', $projectId)->find($focusId);
                if ($focusCar) {
                    $sections[] = "Активный автомобиль в диалоге:\n".$this->cars->formatForAiContext(collect([$focusCar]));
                }
            }
        }

        $sections[] = 'ВАЖНО: не выдумывай цены, VIN и наличие. Если данных нет — честно скажи и предложи уточнить у менеджера.';

        return implode("\n\n", $sections);
    }
}
