<?php

namespace App\Services\ClientBot;

use App\Models\Bot;
use App\Models\ClientManagerRequest;
use App\Models\RentalClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ClientManagerRequestService
{
    public function detectsRequest(string $text): bool
    {
        if (str_starts_with(trim($text), 'cmd_')) {
            return false;
        }

        $lower = mb_strtolower(trim($text));

        foreach ([
            'оператор', 'менеджер', 'человек', 'живой', 'позвоните', 'перезвоните',
            'связаться с', 'свяжите', 'переключите на', 'хочу поговорить',
            'позвать менеджера', 'нужен менеджер', 'живого человека',
        ] as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(manager|operator|human)\b/u', $lower);
    }

    public function submitAndReply(RentalClient $client, string $source, ?string $clientMessage = null): string
    {
        $request = $this->submit($client, $source, $clientMessage);

        return $this->clientReply($client, $request->wasRecentlyCreated);
    }

    public function submit(RentalClient $client, string $source, ?string $clientMessage = null): ClientManagerRequest
    {
        $existing = ClientManagerRequest::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest('id')
            ->first();

        if ($existing) {
            if ($clientMessage && ! $existing->client_message) {
                $existing->update(['client_message' => $clientMessage]);
            }

            return $existing;
        }

        return ClientManagerRequest::query()->create([
            'project_id' => $client->project_id,
            'rental_client_id' => $client->id,
            'bot_id' => $client->bot_id,
            'telegram_user_id' => $client->telegram_user_id,
            'telegram_chat_id' => $client->telegram_chat_id,
            'source' => $source,
            'client_message' => $clientMessage ? trim($clientMessage) : null,
            'status' => 'pending',
        ]);
    }

    public function clientReply(RentalClient $client, bool $created = true): string
    {
        $settings = $this->settingsForClient($client);
        $phone = $settings['phone'] ?? null;
        $custom = trim((string) ($settings['confirm_message'] ?? ''));

        if ($custom !== '') {
            return str_replace(
                ['{name}', '{phone}'],
                [$client->full_name, (string) $phone],
                $custom
            );
        }

        if ($created) {
            $lines = ['<b>Запрос менеджеру принят.</b>'];
            $lines[] = 'Мы передали вашу заявку команде и свяжемся с вами в рабочее время.';
            if ($phone) {
                $lines[] = 'Срочно: <b>'.$phone.'</b>';
            }

            return implode("\n", $lines);
        }

        return 'Ваш запрос менеджеру уже в очереди. Мы свяжемся с вами в ближайшее рабочее время.'
            .($phone ? "\nСрочно: <b>{$phone}</b>" : '');
    }

    /** @return array{phone: ?string, confirm_message: ?string} */
    public function settingsForClient(RentalClient $client): array
    {
        $client->loadMissing('bot');
        $botConfig = $client->bot?->config['manager'] ?? [];

        return [
            'phone' => $botConfig['phone'] ?? config('client_bot_ai.manager_contact'),
            'confirm_message' => $botConfig['confirm_message'] ?? null,
        ];
    }

    /** @return array{phone: ?string, confirm_message: ?string} */
    public function settingsForBot(Bot $bot): array
    {
        $manager = $bot->config['manager'] ?? [];

        return [
            'phone' => $manager['phone'] ?? config('client_bot_ai.manager_contact'),
            'confirm_message' => $manager['confirm_message'] ?? null,
        ];
    }

    public function saveSettings(Bot $bot, ?string $phone, ?string $confirmMessage): void
    {
        $config = $bot->config ?? [];
        $config['manager'] = array_filter([
            'phone' => $phone ? trim($phone) : null,
            'confirm_message' => $confirmMessage ? trim($confirmMessage) : null,
        ], fn ($value) => $value !== null && $value !== '');

        $bot->update(['config' => $config]);
    }

    public function pendingCount(int $projectId): int
    {
        return ClientManagerRequest::query()
            ->where('project_id', $projectId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();
    }

    public function paginateForProject(int $projectId, ?string $status = null, int $perPage = 25): LengthAwarePaginator
    {
        return ClientManagerRequest::query()
            ->where('project_id', $projectId)
            ->with(['client.phones', 'client.contracts', 'handler'])
            ->when($status === 'pending', fn ($q) => $q->whereIn('status', ['pending', 'in_progress']))
            ->when($status && $status !== 'pending', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, ClientManagerRequest> */
    public function latestPending(int $projectId, int $limit = 5): Collection
    {
        return ClientManagerRequest::query()
            ->where('project_id', $projectId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['client'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markInProgress(ClientManagerRequest $request, int $userId): void
    {
        $request->update([
            'status' => 'in_progress',
            'handled_by' => $userId,
        ]);
    }

    public function markResolved(ClientManagerRequest $request, int $userId, ?string $notes = null): void
    {
        $request->update([
            'status' => 'resolved',
            'handled_by' => $userId,
            'handled_at' => now(),
            'admin_notes' => $notes ?? $request->admin_notes,
        ]);
    }

    public function cancel(ClientManagerRequest $request, int $userId, ?string $notes = null): void
    {
        $request->update([
            'status' => 'cancelled',
            'handled_by' => $userId,
            'handled_at' => now(),
            'admin_notes' => $notes ?? $request->admin_notes,
        ]);
    }
}
