<?php

namespace App\Services\ClientBot;

use App\Models\Bot;
use App\Models\RentalClient;

class ClientBotDispatcher
{
    public function __construct(
        private readonly ClientAccountLinker $linker,
        private readonly ClientBotMessageService $messages,
        private readonly ClientSelfServiceService $selfService,
        private readonly ClientBotAiService $clientAi,
        private readonly ClientManagerRequestService $managerRequests,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function dispatch(Bot $bot, array $update): array
    {
        $bot->loadMissing('project');

        $message = $update['message'] ?? null;
        $callback = $update['callback_query'] ?? null;
        $rawText = trim($message['text'] ?? $callback['data'] ?? '');
        $chatId = (int) ($message['chat']['id'] ?? $callback['message']['chat']['id'] ?? 0);
        $userId = (int) ($message['from']['id'] ?? $callback['from']['id'] ?? 0);

        if ($chatId <= 0 || $userId <= 0 || ! $bot->project) {
            return [];
        }

        if (str_starts_with(strtolower($rawText), '/start')) {
            return $this->handleStart($bot, $rawText, $chatId, $userId);
        }

        $client = $this->linker->findByTelegram($bot->id, $userId);

        if ($callback && $rawText === 'link_contract' && ! $client) {
            return array_merge(
                [['type' => 'answer_callback', 'callback_query_id' => (string) $callback['id']]],
                $this->reply($chatId, $this->messages->contractNumberPrompt(), $this->messages->linkKeyboard())
            );
        }

        if (! $client) {
            return $this->handleUnlinked($bot, $message, $rawText, $chatId, $userId);
        }

        if ($client->telegram_chat_id !== $chatId || (int) $client->telegram_user_id !== $userId) {
            $client->update([
                'bot_id' => $bot->id,
                'telegram_user_id' => $userId,
                'telegram_chat_id' => $chatId,
            ]);
        }

        if ($callback) {
            $messageId = (int) ($callback['message']['message_id'] ?? 0);

            return array_merge(
                [['type' => 'answer_callback', 'callback_query_id' => (string) $callback['id']]],
                $this->handleLinkedClient(
                    $bot,
                    $client,
                    $rawText,
                    $chatId,
                    $messageId > 0 ? $messageId : null
                )
            );
        }

        return $this->handleLinkedClient($bot, $client, $rawText, $chatId);
    }

    /** @return array<int, array<string, mixed>> */
    private function handleStart(Bot $bot, string $rawText, int $chatId, int $userId): array
    {
        $parts = preg_split('/\s+/', trim($rawText), 2) ?: [];
        $payload = isset($parts[1]) ? trim($parts[1]) : '';

        if (str_starts_with($payload, 'link_')) {
            $linked = $this->linker->linkByToken(
                $bot->project,
                $bot->id,
                $userId,
                $chatId,
                substr($payload, 5)
            );

            if ($linked) {
                return $this->replyAfterLink($chatId, $this->messages->linkSuccess($linked));
            }
        }

        $existing = $this->linker->findByTelegram($bot->id, $userId);
        if ($existing) {
            return $this->replyLinked($chatId, $this->messages->greeting($existing));
        }

        return $this->reply($chatId, $this->messages->linkPrompt(), $this->messages->linkKeyboard());
    }

    /** @param array<string, mixed>|null $message */
    private function handleUnlinked(Bot $bot, ?array $message, string $rawText, int $chatId, int $userId): array
    {
        if ($rawText === '📄 По номеру договора' || $rawText === 'По номеру договора') {
            return $this->reply($chatId, $this->messages->contractNumberPrompt(), $this->messages->linkKeyboard());
        }

        $contactPhone = $message['contact']['phone_number'] ?? null;
        if ($contactPhone) {
            $linked = $this->linker->linkByPhone($bot->project, $bot->id, $userId, $chatId, (string) $contactPhone);

            return $linked
                ? $this->replyAfterLink($chatId, $this->messages->linkSuccess($linked))
                : $this->reply($chatId, $this->messages->linkFailed(), $this->messages->linkKeyboard());
        }

        if ($rawText !== '' && $this->looksLikeContractNumber($rawText)) {
            $linked = $this->linker->linkByContractNumber($bot->project, $bot->id, $userId, $chatId, $rawText);

            return $linked
                ? $this->replyAfterLink($chatId, $this->messages->linkSuccess($linked))
                : $this->reply($chatId, $this->messages->linkFailed(), $this->messages->linkKeyboard());
        }

        if ($rawText !== '' && $this->looksLikePhone($rawText)) {
            $linked = $this->linker->linkByPhone($bot->project, $bot->id, $userId, $chatId, $rawText);

            return $linked
                ? $this->replyAfterLink($chatId, $this->messages->linkSuccess($linked))
                : $this->reply($chatId, $this->messages->linkFailed(), $this->messages->linkKeyboard());
        }

        if ($rawText !== '' && strlen($rawText) >= 6 && strlen($rawText) <= 20 && ! str_contains($rawText, ' ')) {
            $linked = $this->linker->linkByToken($bot->project, $bot->id, $userId, $chatId, $rawText);
            if ($linked) {
                return $this->replyAfterLink($chatId, $this->messages->linkSuccess($linked));
            }
        }

        return $this->reply($chatId, $this->messages->linkPrompt(), $this->messages->linkKeyboard());
    }

    /** @return array<int, array<string, mixed>> */
    private function handleLinkedClient(
        Bot $bot,
        RentalClient $client,
        string $rawText,
        int $chatId,
        ?int $editMessageId = null,
    ): array {
        $normalized = strtolower($rawText);

        if (in_array($normalized, ['/menu', 'меню', '📋 меню'], true)) {
            return $this->respondLinked($chatId, $this->messages->greeting($client), $editMessageId);
        }

        if ($this->isMenuCommand($rawText)) {
            if ($rawText === 'cmd_manager') {
                return $this->respondLinked(
                    $chatId,
                    $this->managerRequests->submitAndReply($client, 'button'),
                    $editMessageId
                );
            }

            $answer = $this->selfService->commandReply($client, $rawText);
            if ($answer) {
                return $this->respondLinked($chatId, $answer, $editMessageId);
            }
        }

        if ($this->managerRequests->detectsRequest($rawText)) {
            return $this->respondLinked(
                $chatId,
                $this->managerRequests->submitAndReply($client, 'text', $rawText),
                $editMessageId
            );
        }

        $answer = $this->selfService->reply($client, $rawText);
        if ($answer) {
            return $this->respondLinked($chatId, $answer, $editMessageId);
        }

        if ($editMessageId) {
            return $this->respondLinked($chatId, $this->messages->greeting($client), $editMessageId);
        }

        if ($this->clientAi->isEnabled($bot)) {
            $aiActions = $this->clientAi->process($bot, $chatId, $client, $rawText);
            if ($aiActions !== []) {
                return $aiActions;
            }
        }

        return $this->respondLinked($chatId, $this->messages->greeting($client), $editMessageId);
    }

    private function isMenuCommand(string $text): bool
    {
        return str_starts_with($text, 'cmd_');
    }

    /** @return array<int, array<string, mixed>> */
    private function reply(int $chatId, string $text, ?array $replyMarkup = null): array
    {
        $action = [
            'type' => 'send_message',
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $action['reply_markup'] = $replyMarkup;
        }

        return [$action];
    }

    /** @return array<int, array<string, mixed>> */
    private function respondLinked(int $chatId, string $text, ?int $editMessageId = null): array
    {
        $menu = $this->messages->linkedMenu();

        if ($editMessageId) {
            return $this->editMessage($chatId, $editMessageId, $text, $menu);
        }

        return $this->reply($chatId, $text, $menu);
    }

    /** @return array<int, array<string, mixed>> */
    private function editMessage(int $chatId, int $messageId, string $text, ?array $replyMarkup = null): array
    {
        $action = [
            'type' => 'edit_message',
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $action['reply_markup'] = $replyMarkup;
        }

        return [$action];
    }

    /** @return array<int, array<string, mixed>> */
    private function replyLinked(int $chatId, string $text): array
    {
        return $this->respondLinked($chatId, $text);
    }

    /** @return array<int, array<string, mixed>> */
    private function replyAfterLink(int $chatId, string $text): array
    {
        return $this->reply(
            $chatId,
            $text."\n\nВыберите раздел ниже или напишите вопрос своими словами.",
            $this->messages->linkedMenu()
        );
    }

    private function looksLikePhone(string $text): bool
    {
        $digits = preg_replace('/\D+/', '', $text) ?? '';

        return strlen($digits) >= 10;
    }

    private function looksLikeContractNumber(string $text): bool
    {
        $normalized = preg_replace('/\s+/', '', $text) ?? '';

        return (bool) preg_match('/^[A-Za-zА-Яа-яІіЇїЄєҐґ0-9][A-Za-zА-Яа-яІіЇїЄєҐґ0-9\-\/\.]{2,}$/u', $normalized);
    }
}
