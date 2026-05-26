<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Models\Setting;

class BotMessageService
{
    public const KEY = 'bot.messages';

    public function defaults(): array
    {
        return [
            'start' => [
                'text' => "🚗 <b>Pikhlak Auto</b>\n\nДобро пожаловать! Мы помогаем с подбором и доставкой автомобилей.\n\nВыберите действие:",
                'parse_mode' => 'HTML',
                'buttons' => [
                    ['label' => '🚘 Каталог авто', 'callback' => 'cars'],
                    ['label' => '💰 Калькулятор', 'callback' => 'calculator'],
                    ['label' => '📞 Связаться с менеджером', 'callback' => 'manager'],
                ],
            ],
            'callbacks' => [
                'cars' => '🚘 Каталог скоро будет подключён. Пока опишите желаемый автомобиль — AI-ассистент поможет.',
                'calculator' => '💰 Калькулятор доставки в разработке. Напишите марку, год и бюджет.',
                'manager' => '📞 Менеджер свяжется с вами. Оставьте номер телефона в чате.',
                'default' => 'Запрос принят. Напишите подробнее, чем можем помочь.',
            ],
            'default_reply' => '✅ Сообщение получено. AI-ассистент Pikhlak скоро ответит. Для меню отправьте /start',
            'typing_duration' => 1,
        ];
    }

    public function get(int $projectId): array
    {
        return array_replace_recursive(
            $this->defaults(),
            Setting::getValue($projectId, self::KEY, []) ?? []
        );
    }

    public function save(int $projectId, array $messages): void
    {
        Setting::setValue($projectId, self::KEY, $messages);
    }

    public function buildActions(Bot $bot, array $update): array
    {
        $messages = $this->get($bot->project_id);
        $message = $update['message'] ?? null;
        $callback = $update['callback_query'] ?? null;

        $chatId = $message['chat']['id']
            ?? $callback['message']['chat']['id']
            ?? 0;

        $text = strtolower(trim($message['text'] ?? $callback['data'] ?? ''));

        $actions = [
            [
                'type' => 'typing',
                'chat_id' => $chatId,
                'duration' => (int) ($messages['typing_duration'] ?? 1),
            ],
        ];

        if ($text === '/start' || $text === 'start') {
            $actions[] = $this->startAction($chatId, $messages);
        } elseif ($callback) {
            $actions[] = [
                'type' => 'answer_callback',
                'callback_query_id' => (string) $callback['id'],
            ];
            $reply = $messages['callbacks'][$text]
                ?? $messages['callbacks']['default']
                ?? $this->defaults()['callbacks']['default'];
            $actions[] = [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => $reply,
                'parse_mode' => 'HTML',
            ];
        } else {
            $actions[] = [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => $messages['default_reply'] ?? $this->defaults()['default_reply'],
                'parse_mode' => 'HTML',
            ];
        }

        return $actions;
    }

    private function startAction(int $chatId, array $messages): array
    {
        $start = $messages['start'] ?? $this->defaults()['start'];
        $rows = [];
        $row = [];

        foreach ($start['buttons'] ?? [] as $button) {
            $row[] = [
                'text' => $button['label'],
                'callback_data' => $button['callback'],
            ];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row !== []) {
            $rows[] = $row;
        }

        return [
            'type' => 'send_message',
            'chat_id' => $chatId,
            'text' => $start['text'] ?? '',
            'parse_mode' => $start['parse_mode'] ?? 'HTML',
            'reply_markup' => ['inline_keyboard' => $rows],
        ];
    }
}
