<?php

namespace App\Services\ClientBot;

use App\Models\Project;
use App\Models\RentalClient;
use App\Models\RentalClientContract;
use App\Models\RentalClientPhone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClientAccountLinker
{
    public function findByTelegram(int $botId, int $telegramUserId): ?RentalClient
    {
        return RentalClient::query()
            ->where('bot_id', $botId)
            ->where(function ($q) use ($telegramUserId): void {
                $q->where('telegram_user_id', $telegramUserId)
                    ->orWhere('telegram_chat_id', $telegramUserId);
            })
            ->first();
    }

    public function linkByToken(Project $project, int $botId, int $telegramUserId, int $chatId, string $token): ?RentalClient
    {
        $client = RentalClient::query()
            ->where('project_id', $project->id)
            ->where('link_token', Str::lower(trim($token)))
            ->first();

        if ($client) {
            $this->detachTelegramFromSiblings($client, $botId, $telegramUserId);
        }

        return $this->attachTelegram($client, $botId, $telegramUserId, $chatId);
    }

    public function linkByPhone(Project $project, int $botId, int $telegramUserId, int $chatId, string $phone): ?RentalClient
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        $existing = $this->findByTelegram($botId, $telegramUserId);
        if ($existing) {
            return $this->attachTelegram($existing, $botId, $telegramUserId, $chatId);
        }

        $client = $this->findClientByPhone($project, $normalized);

        if ($client) {
            $this->detachTelegramFromSiblings($client, $botId, $telegramUserId);
        }

        return $this->attachTelegram($client, $botId, $telegramUserId, $chatId);
    }

    public function linkByContractNumber(Project $project, int $botId, int $telegramUserId, int $chatId, string $number): ?RentalClient
    {
        $normalized = $this->normalizeContractNumber($number);
        if ($normalized === '') {
            return null;
        }

        $contract = RentalClientContract::query()
            ->whereHas('client', fn ($q) => $q->where('project_id', $project->id))
            ->where('status', 'active')
            ->get()
            ->first(fn (RentalClientContract $row) => $this->normalizeContractNumber((string) $row->contract_number) === $normalized);

        if ($contract?->client) {
            $this->detachTelegramFromSiblings($contract->client, $botId, $telegramUserId);
        }

        return $this->attachTelegram($contract?->client, $botId, $telegramUserId, $chatId);
    }

    /** Переносит Telegram с «двойника» по тому же телефону на текущую карточку. */
    public function claimTelegramForClient(RentalClient $client, int $botId): ?RentalClient
    {
        if ($client->telegram_chat_id || $client->telegram_user_id) {
            return $client->fresh();
        }

        $source = $this->findPhoneTwinWithTelegram($client);
        if (! $source) {
            return null;
        }

        $client->update([
            'bot_id' => $botId,
            'telegram_user_id' => $source->telegram_user_id,
            'telegram_chat_id' => $source->telegram_chat_id,
        ]);

        $source->update([
            'telegram_user_id' => null,
            'telegram_chat_id' => null,
        ]);

        return $client->fresh();
    }

    public function findPhoneTwinWithTelegram(RentalClient $client): ?RentalClient
    {
        $normalized = $client->phones
            ->pluck('phone')
            ->map(fn (string $phone) => $this->normalizePhone($phone))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return null;
        }

        return RentalClient::query()
            ->where('project_id', $client->project_id)
            ->where('id', '!=', $client->id)
            ->where(fn ($q) => $q->whereNotNull('telegram_chat_id')->orWhereNotNull('telegram_user_id'))
            ->with('phones')
            ->get()
            ->first(fn (RentalClient $row) => $this->clientSharesPhone($row, $normalized));
    }

    /** @param  Collection<int, string>  $normalizedPhones */
    private function clientSharesPhone(RentalClient $client, Collection $normalizedPhones): bool
    {
        return $client->phones
            ->pluck('phone')
            ->contains(fn (string $phone) => $normalizedPhones->contains($this->normalizePhone($phone)));
    }

    private function findClientByPhone(Project $project, string $normalized): ?RentalClient
    {
        $clients = RentalClientPhone::query()
            ->whereHas('client', fn ($q) => $q->where('project_id', $project->id))
            ->with('client')
            ->get()
            ->filter(fn (RentalClientPhone $row) => $this->normalizePhone($row->phone) === $normalized)
            ->map(fn (RentalClientPhone $row) => $row->client)
            ->filter()
            ->unique('id')
            ->values();

        if ($clients->isEmpty()) {
            return null;
        }

        $unlinked = $clients->filter(fn (RentalClient $c) => ! $c->telegram_user_id && ! $c->telegram_chat_id);
        if ($unlinked->isNotEmpty()) {
            return $unlinked->sortByDesc('id')->first();
        }

        return $clients->sortByDesc('id')->first();
    }

    private function detachTelegramFromSiblings(RentalClient $client, int $botId, int $telegramUserId): void
    {
        RentalClient::query()
            ->where('project_id', $client->project_id)
            ->where('id', '!=', $client->id)
            ->where(function ($q) use ($telegramUserId): void {
                $q->where('telegram_user_id', $telegramUserId)
                    ->orWhere('telegram_chat_id', $telegramUserId);
            })
            ->update([
                'telegram_user_id' => null,
                'telegram_chat_id' => null,
            ]);
    }

    private function normalizeContractNumber(string $number): string
    {
        return Str::upper(preg_replace('/\s+/', '', trim($number)) ?? '');
    }

    private function attachTelegram(?RentalClient $client, int $botId, int $telegramUserId, int $chatId): ?RentalClient
    {
        if (! $client || $chatId <= 0) {
            return null;
        }

        $client->update([
            'bot_id' => $botId,
            'telegram_user_id' => $telegramUserId,
            'telegram_chat_id' => $chatId,
        ]);

        return $client->fresh();
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '380')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+38'.$digits;
        }

        if (strlen($digits) === 9) {
            return '+380'.$digits;
        }

        return $digits !== '' ? '+'.$digits : '';
    }
}
