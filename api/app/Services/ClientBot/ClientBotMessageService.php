<?php

namespace App\Services\ClientBot;

use App\Models\RentalClient;

/**
 * Тексты интерактивного бота (привязка, self-service).
 * Уведомления клиенту — в config/client_notifications.php + ClientNotify.
 */
class ClientBotMessageService
{
    public function greeting(RentalClient $client): string
    {
        $firstName = explode(' ', trim($client->full_name))[0] ?? $client->full_name;

        return "Добрый день, {$firstName}.\n\n"
            ."Это бот Pikhlak для клиентов аренды. Здесь вы получаете напоминания о платежах, ТО и страховке.\n\n"
            ."Используйте кнопки меню ниже или спросите своими словами, например:\n"
            ."• Сколько мне осталось платить?\n"
            ."• Когда следующее ТО?\n"
            ."• Когда следующий платёж?";
    }

    public function linkPrompt(): string
    {
        return "Подключите аккаунт одним из способов:\n\n"
            ."• Нажмите <b>«Поделиться контактом»</b>\n"
            ."• Или выберите <b>«По номеру договора»</b> и введите номер";
    }

    public function contractNumberPrompt(): string
    {
        return 'Введите <b>номер договора</b> одним сообщением (например: <code>PK-2024-001</code>).';
    }

    /** @return array<string, mixed> */
    public function linkKeyboard(): array
    {
        return [
            'keyboard' => [
                [['text' => '📱 Поделиться контактом', 'request_contact' => true]],
                [['text' => '📄 По номеру договора']],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ];
    }

    /** @return array<string, mixed> */
    public function linkedKeyboard(): array
    {
        return ['remove_keyboard' => true];
    }

    /** @return array<string, mixed> */
    public function linkedMenu(): array
    {
        $rows = [];
        $row = [];

        foreach (config('client_bot_ai.menu', []) as $item) {
            $row[] = [
                'text' => $item['label'],
                'callback_data' => $item['command'],
            ];

            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $rows[] = $row;
        }

        return ['inline_keyboard' => $rows];
    }

    public function linkSuccess(RentalClient $client): string
    {
        $vehicle = $client->currentVehicle();
        $car = $vehicle ? trim($vehicle->make.' '.$vehicle->model) : 'ваш автомобиль';

        return "Аккаунт привязан. {$client->full_name}, вы будете получать уведомления по {$car}.";
    }

    public function linkFailed(): string
    {
        return 'Не удалось найти клиента. Проверьте контакт или номер договора и попробуйте снова.';
    }
}
