<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Models\Chat;
use App\Services\AI\AiKernel;
use App\Services\AI\SettingHelper;
use App\Services\Cars\CarCatalogService;
use App\Services\Chat\ChatStateService;
use App\Services\Chat\OperatorRequestService;
use App\Services\Referral\ReferralTrackingService;
use App\Models\Car;
use App\Models\ReferralLink;

class BotDispatcher
{
    public function __construct(
        private readonly BotMessageService $messages,
        private readonly TelegramUpdatePersister $persister,
        private readonly AiKernel $ai,
        private readonly CarCatalogService $catalog,
        private readonly ChatStateService $chatState,
        private readonly OperatorRequestService $operators,
        private readonly ReferralTrackingService $referrals,
        private readonly BotMessageIntentService $intents,
        private readonly RentCalculatorBotFlow $rentCalculator,
    ) {}

    public function dispatch(Bot $bot, array $update): array
    {
        $bot->loadMissing('project');
        $chat = $this->persister->persist($bot, $update);

        $message = $update['message'] ?? null;
        $callback = $update['callback_query'] ?? null;
        $rawText = trim($message['text'] ?? $callback['data'] ?? '');
        $text = strtolower($rawText);

        if (str_starts_with($text, '/start') || $text === 'start' || str_starts_with($text, 'start ')) {
            return $this->handleStart($bot, $update, $chat, $rawText);
        }

        if ($callback && $text === 'cars') {
            $chatId = $callback['message']['chat']['id'] ?? 0;
            if ($chatId && $bot->project) {
                return array_merge(
                    [['type' => 'answer_callback', 'callback_query_id' => (string) $callback['id']]],
                    $this->catalog->buildTelegramCatalogActions($bot->project, (int) $chatId)
                );
            }
        }

        if ($callback && $text === 'manager') {
            if ($chat && $bot->project) {
                $this->operators->flag($chat, $bot->project);
            }

            $chatId = (int) ($callback['message']['chat']['id'] ?? 0);

            return [
                ['type' => 'answer_callback', 'callback_query_id' => (string) $callback['id']],
                [
                    'type' => 'send_message',
                    'chat_id' => $chatId,
                    'text' => '📞 Запрос передан менеджеру Pikhlak. Ожидайте ответа в этом чате.',
                    'parse_mode' => 'HTML',
                ],
            ];
        }

        if ($callback && $text === 'calculator') {
            if ($chat) {
                $flowActions = $this->rentCalculator->handle($bot, $chat, 'калькулятор', true);
                if ($flowActions !== null) {
                    return array_merge(
                        [['type' => 'answer_callback', 'callback_query_id' => (string) $callback['id']]],
                        $flowActions
                    );
                }
            }

            return $this->messages->buildActions($bot, $update);
        }

        $userText = $message['text'] ?? $callback['data'] ?? '';

        if ($chat && $bot->project && $userText !== '' && $this->operators->detectsOperatorRequest($userText)) {
            $this->operators->flag($chat, $bot->project);

            return [
                ['type' => 'typing', 'chat_id' => $chat->telegram_chat_id, 'duration' => 1],
                [
                    'type' => 'send_message',
                    'chat_id' => $chat->telegram_chat_id,
                    'text' => '📞 Запрос передан менеджеру Pikhlak. Ожидайте ответа в этом чате.',
                    'parse_mode' => 'HTML',
                ],
            ];
        }

        if ($chat && $this->chatState->isHuman($chat)) {
            return [];
        }

        $behavior = SettingHelper::behavior($bot->project_id);
        if (! ($behavior['auto_reply'] ?? true) || ! $chat) {
            return $this->messages->buildActions($bot, $update);
        }

        if ($userText === '') {
            return $this->messages->buildActions($bot, $update);
        }

        try {
            if ($chat) {
                $calcActions = $this->rentCalculator->handle($bot, $chat, $userText);
                if ($calcActions !== null && $calcActions !== []) {
                    return $calcActions;
                }
            }

            $intentActions = $this->intents->resolve($bot, $chat, $userText);
            if ($intentActions !== null && $intentActions !== []) {
                return $intentActions;
            }

            $aiActions = $this->ai->process($bot, $chat, $userText);
            if ($aiActions !== []) {
                return $aiActions;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->messages->buildActions($bot, $update);
    }

    private function handleStart(Bot $bot, array $update, ?Chat $chat, string $rawText): array
    {
        $actions = $this->messages->buildActions($bot, $update);
        $referralLink = null;

        if ($chat) {
            $referralLink = $this->referrals->handleStartCommand($bot, $chat, $rawText);
        }

        if ($referralLink?->landing_message) {
            $chatId = $update['message']['chat']['id']
                ?? $update['callback_query']['message']['chat']['id']
                ?? 0;
            array_splice($actions, 1, 0, [[
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => $referralLink->landing_message,
                'parse_mode' => 'HTML',
            ]]);
        }

        if ($referralLink && $referralLink->car_id && ($referralLink->settings['auto_show_car'] ?? true)) {
            $car = Car::query()->where('project_id', $bot->project_id)->find($referralLink->car_id);
            $chatId = (int) ($update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? 0);
            if ($car && $chatId) {
                $carActions = $this->catalog->buildCarPhotoAlbumActions(
                    $chatId,
                    $car,
                    "🚗 Вы перешли по ссылке на: <b>{$car->make} {$car->model}</b>"
                );
                if ($carActions !== []) {
                    $actions = array_merge($actions, $carActions);
                }
            }
        }

        return $actions;
    }
}
