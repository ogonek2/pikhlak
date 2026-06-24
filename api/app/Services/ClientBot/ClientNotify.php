<?php

namespace App\Services\ClientBot;

use App\Models\ClientNotificationLog;
use App\Models\RentalClient;
use App\Models\RentalClientContract;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use App\Models\RentalClientPhone;
use App\Models\RentalClientVehicle;
use App\Services\Bot\BotRegistry;
use App\Services\ClientBot\Invoice\InvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * Единая точка отправки уведомлений клиенту в Telegram.
 *
 * Пример:
 *   ClientNotify::make()->send($client, 'payment.created', [], $payment);
 */
class ClientNotify
{
    public function __construct(
        private readonly BotRegistry $bots,
        private readonly ClientTelegramApi $telegram,
        private readonly InvoiceService $invoices,
    ) {}

    public static function make(): self
    {
        return app(self::class);
    }

    /**
     * @param  array<string, string|int|float|null>  $variables
     * @param  array<string, mixed>  $options  offset_days, event_date, custom_template
     */
    public function send(
        RentalClient $client,
        string $type,
        array $variables = [],
        ?object $notifiable = null,
        array $options = [],
    ): bool {
        if (! $client->notifications_enabled) {
            return false;
        }

        $chatId = $client->resolveTelegramChatId();
        if (! $chatId) {
            return false;
        }

        $types = config('client_notifications.types', []);
        $typeConfig = is_array($types[$type] ?? null) ? $types[$type] : null;
        $template = $options['custom_template'] ?? $typeConfig['template'] ?? null;

        if ($template === null || $template === '') {
            Log::warning('ClientNotify: unknown notification type', ['type' => $type]);

            return false;
        }

        $client->loadMissing('project');
        $bot = $this->bots->client($client->project);

        if (! $bot->is_active || ! $bot->telegram_token) {
            return false;
        }

        $needsInvoice = in_array('invoice', $typeConfig['attachments'] ?? [], true);

        $vars = array_merge(
            $this->baseVariables($client),
            $this->variablesFromNotifiable($client, $notifiable, $needsInvoice),
            $variables,
        );

        $vars = $this->applyComputedBlocks($client, $vars, $type, $options);
        $text = $this->render($template, $vars);

        $messageId = $this->telegram->sendMessage($bot, $chatId, $text);

        if (! empty($typeConfig['attachments'])) {
            $this->sendAttachments($bot, $chatId, $client, $notifiable, $typeConfig['attachments']);
        }

        $this->log(
            client: $client,
            type: (string) ($options['log_event_type'] ?? $type),
            notifiable: $notifiable,
            offsetDays: (int) ($options['offset_days'] ?? 0),
            eventDate: (string) ($options['event_date'] ?? now()->toDateString()),
            messageId: $messageId,
        );

        return $messageId !== null;
    }

    /** @param  array<string, string|int|float|null>  $variables */
    public function render(string $template, array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{(\w+)\}/',
            static fn (array $m): string => (string) ($variables[$m[1]] ?? ''),
            $template,
        );
    }

    /** @return array<string, string> */
    private function baseVariables(RentalClient $client): array
    {
        $firstName = explode(' ', trim($client->full_name))[0] ?? $client->full_name;
        $vehicle = $client->currentVehicle();
        $contract = $client->activeContract();
        $currency = $contract?->currency ?? 'UAH';

        return [
            'first_name' => $firstName,
            'full_name' => $client->full_name,
            'car' => $vehicle ? $vehicle->title() : 'ваш автомобиль',
            'contract_number' => $contract?->contract_number ?? '',
            'contract_number_block' => $contract?->contract_number
                ? ' № <b>'.$contract->contract_number.'</b>'
                : '',
            'currency' => config('client_bot.currency_symbols.'.$currency, $currency),
            'monthly_amount' => $contract
                ? number_format((float) $contract->monthly_amount, 0, '.', ' ')
                : '—',
        ];
    }

    /** @return array<string, string> */
    private function variablesFromNotifiable(RentalClient $client, ?object $notifiable, bool $withInvoice = false): array
    {
        return match (true) {
            $notifiable instanceof RentalClientPayment => $this->paymentVariables($client, $notifiable, $withInvoice),
            $notifiable instanceof RentalClientMaintenance => $this->maintenanceVariables($client, $notifiable),
            $notifiable instanceof RentalClientInsurance => $this->insuranceVariables($notifiable),
            $notifiable instanceof RentalClientContract => $this->contractVariables($notifiable),
            $notifiable instanceof RentalClientVehicle => $this->vehicleVariables($notifiable),
            $notifiable instanceof RentalClientPhone => [
                'phone' => $notifiable->phone,
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    private function paymentVariables(RentalClient $client, RentalClientPayment $payment, bool $withInvoice = false): array
    {
        $contract = $client->activeContract();
        $currency = config('client_bot.currency_symbols.'.($contract?->currency ?? 'UAH'), 'грн');
        $statuses = config('client_portal.payment_statuses', []);

        $vars = [
            'amount' => number_format((float) $payment->amount, 0, '.', ' '),
            'due_date' => $payment->due_date?->format('d.m.Y') ?? '—',
            'payment_status' => $statuses[$payment->status] ?? $payment->status,
            'currency' => $currency,
            'payment_url' => '',
            'payment_url_block' => '',
        ];

        if ($withInvoice) {
            try {
                $invoice = $this->invoices->issueForPayment($client, $payment);
                $vars['payment_url'] = $invoice->payment_url ?? '';
                $vars['payment_url_block'] = $invoice->payment_url
                    ? "\n\nОплатить:\n{$invoice->payment_url}"
                    : '';
            } catch (\Throwable $e) {
                Log::warning('ClientNotify: invoice generation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $vars;
    }

    /** @return array<string, string> */
    private function maintenanceVariables(RentalClient $client, RentalClientMaintenance $maintenance): array
    {
        $vehicle = $maintenance->vehicle ?? $client->currentVehicle();
        $types = config('client_bot.maintenance_type_map', []);
        $eventTypes = config('client_bot.event_types', []);
        $mapped = $eventTypes[$types[$maintenance->type] ?? 'maintenance'] ?? 'обслуживание';

        return [
            'title' => $maintenance->title ?: $mapped,
            'date' => $maintenance->scheduled_at?->format('d.m.Y') ?? '—',
            'car' => $vehicle ? $vehicle->title() : 'ваш автомобиль',
        ];
    }

    /** @return array<string, string> */
    private function insuranceVariables(RentalClientInsurance $insurance): array
    {
        return [
            'provider' => $insurance->provider,
            'date' => $insurance->valid_until?->format('d.m.Y') ?? '—',
        ];
    }

    /** @return array<string, string> */
    private function contractVariables(RentalClientContract $contract): array
    {
        return [
            'contract_number' => $contract->contract_number ?? '',
            'contract_number_block' => $contract->contract_number
                ? ' № <b>'.$contract->contract_number.'</b>'
                : '',
            'monthly_amount' => number_format((float) $contract->monthly_amount, 0, '.', ' '),
            'currency' => config('client_bot.currency_symbols.'.$contract->currency, $contract->currency),
        ];
    }

    /** @return array<string, string> */
    private function vehicleVariables(RentalClientVehicle $vehicle): array
    {
        return [
            'car' => $vehicle->title(),
        ];
    }

    /**
     * @param  array<string, string|int|float|null>  $vars
     * @param  array<string, mixed>  $options
     * @return array<string, string|int|float|null>
     */
    private function applyComputedBlocks(RentalClient $client, array $vars, string $type, array $options): array
    {
        $offset = (int) ($options['offset_days'] ?? 0);
        $contract = $client->activeContract();

        if (str_starts_with($type, 'payment.')) {
            $vars['buyout_suffix'] = ($contract?->buyout_option ?? false) ? ' с правом выкупа' : '';

            if ($type === 'payment.reminder') {
                $vars['payment_prefix'] = match (true) {
                    $offset < 0 => 'Напоминаем',
                    $offset === 0 => 'Сегодня',
                    default => 'Просрочен платёж',
                };
            }
        }

        if ($type === 'maintenance.reminder') {
            $vars['maintenance_prefix'] = $offset <= 0
                ? 'Ваш автомобиль требует прохождения'
                : 'Напоминаем:';
        }

        if ($type === 'insurance.reminder') {
            $vars['insurance_suffix'] = $offset <= 0
                ? 'Пожалуйста, продлите страховку.'
                : 'Рекомендуем заранее оформить продление.';
        }

        return $vars;
    }

    /** @param  list<string>  $attachments */
    private function sendAttachments(
        $bot,
        int $chatId,
        RentalClient $client,
        ?object $notifiable,
        array $attachments,
    ): void {
        if (! in_array('invoice', $attachments, true) || ! $notifiable instanceof RentalClientPayment) {
            return;
        }

        try {
            $invoice = $this->invoices->issueForPayment($client, $notifiable);
            $pdf = $this->invoices->absolutePath($invoice->pdf_path);
            if ($pdf) {
                $this->telegram->sendDocument($bot, $chatId, $pdf, 'Счёт '.$invoice->invoice_number);
            }

            $qr = $this->invoices->absolutePath($invoice->qr_path);
            if ($qr) {
                $this->telegram->sendPhoto($bot, $chatId, $qr, 'QR для оплаты');
            }
        } catch (\Throwable $e) {
            Log::warning('ClientNotify: attachment failed', ['error' => $e->getMessage()]);
        }
    }

    private function log(
        RentalClient $client,
        string $type,
        ?object $notifiable,
        int $offsetDays,
        string $eventDate,
        ?int $messageId,
    ): void {
        if (! $notifiable) {
            return;
        }

        try {
            ClientNotificationLog::query()->create([
                'rental_client_id' => $client->id,
                'event_type' => $type,
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->getKey(),
                'offset_days' => $offsetDays,
                'event_date' => $eventDate,
                'status' => $messageId ? 'sent' : 'failed',
                'telegram_message_id' => $messageId,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ClientNotify: log failed', ['error' => $e->getMessage(), 'type' => $type]);
        }
    }
}
