<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Models\Car;
use App\Models\Chat;
use App\Models\Project;
use App\Services\Cars\CarCatalogService;
use App\Services\Rental\RentBuyoutCalculator;

/**
 * Сценарий расчёта аренды с выкупом в боте прогрева (до вызова ИИ).
 * После первого расчёта сохраняет параметры — можно менять взнос, срок или авто без повторного опроса.
 */
class RentCalculatorBotFlow
{
    private const FLOW_KEY = 'rent_calc';

    /** @var list<string> */
    private const START_KEYWORDS = [
        'калькулятор', 'расчёт', 'расчет', 'просчитай', 'просчитать', 'посчитай', 'посчитать',
        'аренда с выкупом', 'право выкупа', 'сколько платить', 'сколько стоит',
        'еженедельн', 'в неделю', 'стоимость аренды',
    ];

    /** @var list<string> */
    private const RESTART_KEYWORDS = [
        'заново', 'сначала', 'с нуля', 'новый расч', 'новый расчёт', 'новый расчет',
    ];

    public function __construct(
        private readonly RentBuyoutCalculator $calculator,
        private readonly CarCatalogService $catalog,
    ) {}

    public function handle(Bot $bot, Chat $chat, string $userText, bool $forceStart = false): ?array
    {
        $project = $bot->project;
        if (! $project) {
            return null;
        }

        $chatId = (int) $chat->telegram_chat_id;
        $state = $chat->state ?? [];
        $flow = $state[self::FLOW_KEY] ?? null;
        $lower = mb_strtolower(trim($userText));

        if ($this->isCancel($lower)) {
            $this->clearFlow($chat);

            return $this->actions($chatId, 'Расчёт отменён. Напишите «калькулятор», чтобы начать снова.');
        }

        if (is_array($flow) && ($flow['step'] ?? '') === 'done') {
            if ($forceStart || $this->isExplicitRestart($lower)) {
                $this->setFlow($chat, ['step' => 'term']);

                return $this->actions($chatId, $this->askTerm());
            }

            $recalc = $this->tryRecalculate($chat, $chatId, $userText, $project, $flow);
            if ($recalc !== null) {
                return $recalc;
            }

            return null;
        }

        if ($forceStart || ($flow === null && $this->isCalculatorIntent($lower))) {
            $this->setFlow($chat, ['step' => 'term']);

            return $this->actions($chatId, $this->askTerm());
        }

        if (! is_array($flow) || empty($flow['step'])) {
            return null;
        }

        return match ($flow['step']) {
            'term' => $this->handleTerm($chat, $chatId, $userText, $project),
            'car' => $this->handleCar($chat, $chatId, $userText, $project),
            'first_payment' => $this->handleFirstPayment($chat, $chatId, $userText, $project),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $flow
     * @return list<array<string, mixed>>|null
     */
    private function tryRecalculate(Chat $chat, int $chatId, string $userText, Project $project, array $flow): ?array
    {
        $lower = mb_strtolower(trim($userText));
        $updates = $this->parseParamUpdates($userText, $project, $flow);

        if ($updates === [] && ! $this->looksLikeParamChange($lower)) {
            return null;
        }

        if ($updates === [] && $this->looksLikeParamChange($lower)) {
            return $this->actions($chatId, $this->recalcHint($flow));
        }

        $params = $this->mergeParams($flow, $updates, $project);
        if ($params === null) {
            return $this->actions($chatId, 'Не удалось определить авто. Напишите марку/модель или номер из каталога.');
        }

        try {
            $result = $this->calculator->calculate([
                'car_price' => $params['car_price'],
                'first_payment' => $params['first_payment'],
                'term_years' => $params['term_years'],
                'currency' => $params['currency'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->actions($chatId, $e->getMessage()."\n\n".$this->recalcHint($flow));
        }

        $this->saveDoneState($chat, $params);

        $changes = $this->describeChanges($flow, $params);
        $prefix = $changes !== '' ? "🔄 <b>Пересчёт</b> ({$changes})\n\n" : "🔄 <b>Пересчёт</b>\n\n";

        $text = $prefix
            .$this->calculator->formatTelegramMessage($result, $params['car_title'])
            ."\n\n".$this->recalcFooter();

        return $this->actions($chatId, $text);
    }

    /**
     * @param  array<string, mixed>  $flow
     * @param  array<string, mixed>  $updates
     * @return array{term_years: int, first_payment: float, car_id: int, car_title: string, car_price: float, currency: string}|null
     */
    private function mergeParams(array $flow, array $updates, Project $project): ?array
    {
        $carId = (int) ($updates['car_id'] ?? $flow['car_id'] ?? 0);
        $car = Car::query()->where('project_id', $project->id)->find($carId);
        if (! $car) {
            return null;
        }

        return [
            'term_years' => (int) ($updates['term_years'] ?? $flow['term_years'] ?? config('rent_buyout.default_term_years', 3)),
            'first_payment' => (float) ($updates['first_payment'] ?? $flow['first_payment'] ?? 0),
            'car_id' => $car->id,
            'car_title' => $car->title(),
            'car_price' => (float) $car->price,
            'currency' => $car->currency ?? 'USD',
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function parseParamUpdates(string $text, Project $project, array $current): array
    {
        $updates = [];
        $lower = mb_strtolower(trim($text));

        if (preg_match('/(?:на\s+)?(\d{1,2})\s*(?:год|года|лет)\b/u', $lower, $m)) {
            $updates['term_years'] = (int) $m[1];
        } elseif (preg_match('/\bсрок\s*[:\-]?\s*(\d{1,2})\b/u', $lower, $m)) {
            $updates['term_years'] = (int) $m[1];
        }

        $firstPayment = $this->parseFirstPaymentFromText($text, $lower);
        if ($firstPayment !== null) {
            $updates['first_payment'] = $firstPayment;
        }

        $car = $this->resolveCar($project, $text);
        if ($car && (int) ($current['car_id'] ?? 0) !== $car->id && $this->textReferencesCar($lower, $car, $text)) {
            $updates['car_id'] = $car->id;
        }

        return array_filter($updates, fn ($v) => $v !== null);
    }

    private function parseFirstPaymentFromText(string $text, string $lower): ?float
    {
        if (preg_match('/(?:перв\w*\s*)?взнос\w*(?:\s+в\s+размере)?\s*(?:в\s+)?[\$]?\s*([\d\s.,]+)/u', $lower, $m)) {
            return $this->parseMoney($m[1]);
        }

        if (preg_match('/(?:заплачу|внесу|отдам|плат\w*|влож\w*)\s*(?:в\s+качестве\s+перв\w*\s*взнос\w*)?\s*[\$]?\s*([\d\s.,]+)/u', $lower, $m)) {
            return $this->parseMoney($m[1]);
        }

        if (preg_match('/(?:плат\w*|внес\w*)\s*[\$]?\s*([\d\s.,]+)/u', $lower, $m)) {
            return $this->parseMoney($m[1]);
        }

        if (preg_match('/([\d\s.,]+)\s*(?:\$|доллар\w*|usd)\b/u', $lower, $m)) {
            return $this->parseMoney($m[1]);
        }

        if ($this->isLikelyPaymentOnlyMessage($lower)) {
            return $this->parseMoney($text);
        }

        return null;
    }

    private function isLikelyPaymentOnlyMessage(string $lower): bool
    {
        $trimmed = trim($lower);

        if (preg_match('/^(?:а\s+)?(?:если\s+)?(?:я\s+)?(?:заплачу|внесу|отдам\s+)?[\d\s.,]+(?:\s*\$)?$/u', $trimmed)) {
            return true;
        }

        if (preg_match('/^а\s+если\s+/u', $trimmed) && preg_match('/\d/u', $trimmed) && ! preg_match('/\d+\s*(?:год|года|лет)/u', $trimmed)) {
            return true;
        }

        return (bool) preg_match('/^\d[\d\s.,]*$/u', $trimmed);
    }

    private function mentionsCarChange(string $lower): bool
    {
        foreach ([
            'другое авто', 'другую машину', 'другой автомобиль', 'смени авто', 'поменяй авто',
            'вместо этой', 'другая машина',
        ] as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function textReferencesCar(string $lower, Car $car, string $rawText): bool
    {
        if ($this->mentionsCarChange($lower)) {
            return true;
        }

        if (preg_match('/^\d{1,2}$/', trim($rawText))) {
            return true;
        }

        $make = mb_strtolower(trim((string) $car->make));
        $model = mb_strtolower(trim((string) $car->model));

        if ($make !== '' && str_contains($lower, $make)) {
            return true;
        }

        return $model !== '' && str_contains($lower, $model);
    }

    private function looksLikeParamChange(string $lower): bool
    {
        if ($this->isLikelyPaymentOnlyMessage($lower)) {
            return true;
        }

        foreach ([
            'пересчит', 'посчитай', 'просчитай', 'а если', 'если я', 'измени', 'поменя', 'вместо',
            'другой взнос', 'другую', 'другое', 'взнос', 'первый', 'срок', 'платеж', 'заплачу', 'внесу',
        ] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return (bool) preg_match('/\d+\s*(?:год|года|лет)\b/u', $lower);
    }

    private function isCalculatorIntent(string $lower): bool
    {
        foreach (self::START_KEYWORDS as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function isExplicitRestart(string $lower): bool
    {
        if ($lower === 'калькулятор' || $lower === 'новый калькулятор') {
            return true;
        }

        foreach (self::RESTART_KEYWORDS as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function isCancel(string $lower): bool
    {
        return in_array($lower, ['отмена', 'cancel', 'стоп'], true);
    }

    private function handleTerm(Chat $chat, int $chatId, string $userText, Project $project): array
    {
        $years = $this->parseInteger($userText);
        if ($years === null || $years < 1 || $years > 10) {
            return $this->actions($chatId, 'Укажите срок аренды <b>числом лет</b> (например: 3).');
        }

        $this->setFlow($chat, [
            'step' => 'car',
            'term_years' => $years,
        ]);

        return $this->actions($chatId, $this->askCar($project));
    }

    private function handleCar(Chat $chat, int $chatId, string $userText, Project $project): array
    {
        $car = $this->resolveCar($project, $userText);
        if (! $car) {
            return $this->actions($chatId, $this->askCar($project)."\n\nНе удалось определить авто. Выберите номер из списка или напишите марку и модель.");
        }

        $flow = ($chat->fresh()->state ?? [])[self::FLOW_KEY] ?? [];

        $this->setFlow($chat, [
            'step' => 'first_payment',
            'term_years' => (int) ($flow['term_years'] ?? config('rent_buyout.default_term_years', 3)),
            'car_id' => $car->id,
            'car_title' => $car->title(),
            'car_price' => (float) $car->price,
            'currency' => $car->currency ?? 'USD',
        ]);

        $sym = config('client_bot.currency_symbols.'.$car->currency, $car->currency);
        $price = number_format((float) $car->price, 0, '.', ' ');

        return $this->actions($chatId, "Выбрано: <b>{$car->title()}</b> — {$price} {$sym}\n\n".$this->askFirstPayment());
    }

    private function handleFirstPayment(Chat $chat, int $chatId, string $userText, Project $project): array
    {
        $amount = $this->parseMoney($userText);
        $flow = ($chat->fresh()->state ?? [])[self::FLOW_KEY] ?? [];
        $carPrice = (float) ($flow['car_price'] ?? 0);

        if ($amount === null || $amount < 0 || ($carPrice > 0 && $amount >= $carPrice)) {
            return $this->actions($chatId, 'Укажите сумму <b>первого взноса</b> числом (например: 2000).');
        }

        $car = Car::query()->where('project_id', $project->id)->find($flow['car_id'] ?? 0);
        if (! $car) {
            $this->clearFlow($chat);

            return $this->actions($chatId, 'Авто не найдено. Начните расчёт заново: «калькулятор».');
        }

        $params = [
            'term_years' => (int) ($flow['term_years'] ?? 3),
            'first_payment' => $amount,
            'car_id' => $car->id,
            'car_title' => $car->title(),
            'car_price' => (float) $car->price,
            'currency' => $car->currency ?? 'USD',
        ];

        try {
            $result = $this->calculator->calculate([
                'car_price' => $params['car_price'],
                'first_payment' => $params['first_payment'],
                'term_years' => $params['term_years'],
                'currency' => $params['currency'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->actions($chatId, $e->getMessage());
        }

        $this->saveDoneState($chat, $params);

        $text = $this->calculator->formatTelegramMessage($result, $car->title())
            ."\n\n".$this->recalcFooter();

        return $this->actions($chatId, $text);
    }

    /**
     * @param  array{term_years: int, first_payment: float, car_id: int, car_title: string, car_price: float, currency: string}  $params
     */
    private function saveDoneState(Chat $chat, array $params): void
    {
        $this->setFlow($chat, [
            'step' => 'done',
            'term_years' => $params['term_years'],
            'first_payment' => $params['first_payment'],
            'car_id' => $params['car_id'],
            'car_title' => $params['car_title'],
            'car_price' => $params['car_price'],
            'currency' => $params['currency'],
        ]);
    }

    /** @param  array<string, mixed>  $flow */
    private function recalcHint(array $flow): string
    {
        $sym = config('client_bot.currency_symbols.'.($flow['currency'] ?? 'USD'), $flow['currency'] ?? 'USD');
        $payment = number_format((float) ($flow['first_payment'] ?? 0), 0, '.', ' ');

        return 'Текущий расчёт: <b>'.($flow['car_title'] ?? 'авто').'</b>, '
            .($flow['term_years'] ?? '?').' лет, взнос <b>'.$payment.' '.$sym."</b>.\n\n"
            .'Напишите, что изменить, например: <code>взнос 3000</code>, <code>на 2 года</code>, <code>Kia K5</code>.';
    }

    private function recalcFooter(): string
    {
        return "✏️ Можно сразу поменять параметры — пересчитаю без повторных вопросов:\n"
            ."• <code>взнос 3000</code> или просто <code>3000</code>\n"
            ."• <code>на 2 года</code>\n"
            ."• <code>Hyundai Sonata</code> (другое авто)\n\n"
            .'Оформить договор — напишите «менеджер». Новый расчёт с нуля — «калькулятор».';
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array{term_years: int, first_payment: float, car_title: string}  $after
     */
    private function describeChanges(array $before, array $after): string
    {
        $parts = [];

        if ((int) ($before['term_years'] ?? 0) !== (int) $after['term_years']) {
            $parts[] = 'срок '.$after['term_years'].' '.($after['term_years'] === 1 ? 'год' : 'лет');
        }

        if ((float) ($before['first_payment'] ?? 0) !== (float) $after['first_payment']) {
            $sym = config('client_bot.currency_symbols.'.($before['currency'] ?? 'USD'), 'USD');
            $parts[] = 'взнос '.number_format($after['first_payment'], 0, '.', ' ').' '.$sym;
        }

        if (($before['car_title'] ?? '') !== ($after['car_title'] ?? '')) {
            $parts[] = $after['car_title'];
        }

        return implode(', ', $parts);
    }

    private function askTerm(): string
    {
        return "📊 <b>Калькулятор аренды с правом выкупа</b>\n\n"
            ."Шаг 1 из 3: на сколько <b>лет</b> планируете аренду?\n"
            .'(например: <code>3</code>)';
    }

    private function askCar(Project $project): string
    {
        $cars = Car::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['published', 'reserved'])
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        if ($cars->isEmpty()) {
            return "Шаг 2 из 3: напишите <b>марку и модель</b> интересующего авто.";
        }

        $lines = ["Шаг 2 из 3: выберите авто <b>номером</b> или напишите марку/модель:", ''];
        foreach ($cars as $i => $car) {
            $sym = config('client_bot.currency_symbols.'.$car->currency, $car->currency);
            $price = number_format((float) $car->price, 0, '.', ' ');
            $lines[] = ($i + 1).'. <b>'.$car->title()."</b> — {$price} {$sym}";
        }

        return implode("\n", $lines);
    }

    private function askFirstPayment(): string
    {
        return 'Шаг 3 из 3: какой <b>первый взнос</b> планируете? (сумма в $, например: <code>2000</code>)';
    }

    private function resolveCar(Project $project, string $userText): ?Car
    {
        if (preg_match('/^\d{1,2}$/', trim($userText))) {
            $index = (int) trim($userText) - 1;
            $cars = Car::query()
                ->where('project_id', $project->id)
                ->whereIn('status', ['published', 'reserved'])
                ->orderByDesc('published_at')
                ->limit(10)
                ->get();
            if ($index >= 0 && $index < $cars->count()) {
                return $cars[$index];
            }
        }

        if (preg_match('/id[:\s]*(\d+)/iu', $userText, $m)) {
            return Car::query()->where('project_id', $project->id)->find((int) $m[1]);
        }

        return $this->catalog->findInterestedCar($project->id, $userText);
    }

    private function parseInteger(string $text): ?int
    {
        if (preg_match('/(\d{1,2})/', trim($text), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function parseMoney(string $text): ?float
    {
        $clean = preg_replace('/[^\d.,]/', '', $text) ?? '';
        $clean = str_replace(',', '.', $clean);
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    /** @param  array<string, mixed>  $flow */
    private function setFlow(Chat $chat, array $flow): void
    {
        $state = $chat->state ?? [];
        $state[self::FLOW_KEY] = $flow;
        $chat->update(['state' => $state]);
    }

    private function clearFlow(Chat $chat): void
    {
        $state = $chat->state ?? [];
        unset($state[self::FLOW_KEY]);
        $chat->update(['state' => $state]);
    }

    /** @return list<array<string, mixed>> */
    private function actions(int $chatId, string $text): array
    {
        return [
            ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
            [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ],
        ];
    }
}
