<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Models\Car;
use App\Models\Chat;
use App\Models\Project;
use App\Services\Cars\CarCatalogService;
use App\Services\CRM\LeadWarmingService;

/**
 * Распознавание намерений до ИИ: фото, каталог, карточка авто по неполным фразам.
 */
class BotMessageIntentService
{
    public function __construct(
        private readonly CarCatalogService $catalog,
        private readonly LeadWarmingService $warming,
    ) {}

    public function resolve(Bot $bot, Chat $chat, string $userText): ?array
    {
        $project = $bot->project;
        if (! $project) {
            return null;
        }

        $chatId = (int) $chat->telegram_chat_id;
        $lead = $this->warming->ensureLead($chat, $project);

        if ($this->catalog->isPhotoRequest($userText)) {
            return $this->handlePhoto($project, $chatId, $userText, $lead);
        }

        if ($this->catalog->isCatalogInquiry($userText)) {
            return $this->catalog->buildTelegramCatalogActions($project, $chatId);
        }

        $car = $this->catalog->findInterestedCar($project->id, $userText);
        if ($car && $this->catalog->isCarDetailInquiry($userText)) {
            $lead->update(['car_interest_id' => $car->id]);
            $this->warming->scoreFromMessage($lead, $userText, $this->catalog->formatCarDetail($car));

            return [
                ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
                [
                    'type' => 'send_message',
                    'chat_id' => $chatId,
                    'text' => $this->catalog->formatCarDetail($car),
                    'parse_mode' => 'HTML',
                ],
                [
                    'type' => 'send_message',
                    'chat_id' => $chatId,
                    'text' => 'Чтобы посмотреть <b>фото</b> — напишите «фото '.$car->make.' '.$car->model.'» или «фото ID:'.$car->id.'».',
                    'parse_mode' => 'HTML',
                ],
            ];
        }

        return null;
    }

    private function handlePhoto(Project $project, int $chatId, string $userText, $lead): array
    {
        $car = $this->catalog->resolveCarForPhotoRequest($project->id, $userText, $lead);

        if (! $car) {
            $candidates = $this->catalog->findCarCandidates($project->id, $userText, 5);

            if ($candidates->count() === 1) {
                $car = $candidates->first();
            } elseif ($candidates->count() > 1) {
                $lines = ['📷 Уточните, фото какого авто отправить:', ''];
                foreach ($candidates as $c) {
                    $lines[] = "• <b>{$c->title()}</b> — напишите «фото {$c->make} {$c->model}» или «фото ID:{$c->id}»";
                }

                return [
                    ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
                    [
                        'type' => 'send_message',
                        'chat_id' => $chatId,
                        'text' => implode("\n", $lines),
                        'parse_mode' => 'HTML',
                    ],
                ];
            }
        }

        if ($car) {
            $car->loadMissing('media');
            $lead->update(['car_interest_id' => $car->id]);
            $this->warming->scoreFromMessage($lead, $userText, 'photo');

            return $this->catalog->buildCarPhotoAlbumActions($chatId, $car);
        }

        return [
            ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
            [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => '📷 Не нашёл такое авто в каталоге. Напишите марку и модель (например: «фото Sonata») или ID из списка — /start → Каталог.',
                'parse_mode' => 'HTML',
            ],
        ];
    }
}
