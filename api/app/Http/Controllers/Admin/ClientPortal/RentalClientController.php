<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Admin\ClientPortal\Concerns\NotifiesRentalClient;
use App\Http\Controllers\Admin\ClientPortal\Concerns\RespondsWithClientProfile;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Project;
use App\Models\RentalClient;
use App\Services\ClientPortal\RentalClientProfileService;
use App\Services\Rental\RentalClientOnboardingService;
use App\Services\Bot\BotRegistry;
use App\Services\ClientBot\ClientAccountLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RentalClientController extends Controller
{
    use NotifiesRentalClient;
    use RespondsWithClientProfile;

    public function __construct(
        private readonly RentalClientProfileService $profile,
        private readonly RentalClientOnboardingService $onboarding,
        private readonly ClientAccountLinker $linker,
        private readonly BotRegistry $bots,
    ) {}

    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = RentalClient::query()
            ->with(['phones', 'vehicles', 'contracts'])
            ->where('project_id', $project->id);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('phones', fn ($pq) => $pq->where('phone', 'like', $like))
                    ->orWhereHas('vehicles', fn ($vq) => $vq
                        ->where('plate_number', 'like', $like)
                        ->orWhere('make', 'like', $like)
                        ->orWhere('model', 'like', $like));
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return view('admin.client.clients.index', [
            'clients' => $query->latest()->paginate(20)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'statuses' => config('client_portal.client_statuses', []),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $cars = Car::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['published', 'reserved', 'draft'])
            ->orderByDesc('published_at')
            ->orderBy('make')
            ->get();

        return view('admin.client.clients.create', [
            'client' => new RentalClient(['status' => 'active']),
            'statuses' => config('client_portal.client_statuses', []),
            'cars' => $cars,
            'defaults' => [
                'term_years' => config('rent_buyout.default_term_years', 3),
                'overpayment_rate' => config('rent_buyout.default_overpayment_rate', 0.40),
                'rent_start' => now()->format('Y-m-d'),
            ],
            'currencySymbols' => config('client_bot.currency_symbols', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'primary_phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'status' => ['required', 'in:active,paused,completed,archived'],
            'notes' => ['nullable', 'string'],
            'car_id' => ['required', 'exists:cars,id'],
            'first_payment' => ['required', 'numeric', 'min:0'],
            'term_years' => ['required', 'integer', 'min:1', 'max:15'],
            'overpayment_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'rent_start' => ['nullable', 'date'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'contract_number' => ['nullable', 'string', 'max:60'],
        ]);

        $clientData = $this->validateClient($request);

        $client = RentalClient::query()->create([
            ...$clientData,
            'project_id' => $project->id,
            'bot_id' => app(\App\Services\Bot\BotRegistry::class)->client($project)->id,
            'link_token' => Str::lower(Str::random(10)),
            'notifications_enabled' => true,
        ]);

        if ($phone = trim((string) $request->input('primary_phone'))) {
            $client->phones()->create([
                'label' => 'mobile',
                'phone' => $phone,
                'is_primary' => true,
            ]);
        }

        $overpaymentRate = $data['overpayment_rate'] ?? null;
        if ($overpaymentRate !== null && $overpaymentRate > 1) {
            $overpaymentRate = $overpaymentRate / 100;
        }

        $provision = $this->onboarding->provision($client, [
            'car_id' => (int) $data['car_id'],
            'first_payment' => $data['first_payment'],
            'term_years' => (int) $data['term_years'],
            'overpayment_rate' => $overpaymentRate,
            'rent_start' => $data['rent_start'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'contract_number' => $data['contract_number'] ?? null,
        ]);

        $this->notifyClient($client, 'contract.created', [], $provision['contract']);

        return redirect()
            ->route('admin.client.clients.show', [$client, 'tab' => 'contracts'])
            ->with('success', 'Клиент создан. Сгенерированы договор, недельные платежи, ТО и замена масла.');
    }

    public function show(Request $request, RentalClient $client): View
    {
        $this->ensureProjectClient($request, $client);

        return view('admin.client.clients.show', [
            'client' => $client,
            'profilePayload' => $this->profile->toArray($request, $client),
        ]);
    }

    public function edit(Request $request, RentalClient $client): RedirectResponse
    {
        $this->ensureProjectClient($request, $client);

        return redirect()->route('admin.client.clients.show', $client);
    }

    public function update(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);

        $data = $this->validateClient($request);

        if (! $request->filled('telegram_chat_id') && $client->telegram_chat_id) {
            $data['telegram_chat_id'] = $client->telegram_chat_id;
        }

        if (! $request->filled('telegram_user_id') && $client->telegram_user_id) {
            $data['telegram_user_id'] = $client->telegram_user_id;
        }

        $client->update($data);

        $this->notifyClient($client, 'profile.updated', [], $client);

        return $this->clientProfileResponse($request, $client, 'Клиент обновлён.');
    }

    public function claimTelegram(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);

        /** @var Project $project */
        $project = $request->attributes->get('project');
        $claimed = $this->linker->claimTelegramForClient($client->load('phones'), $this->bots->client($project)->id);

        if (! $claimed) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Не найдена привязка Telegram по этому телефону.'], 422);
            }

            return back()->with('error', 'Не найдена привязка Telegram по этому телефону.');
        }

        return $this->clientProfileResponse($request, $claimed, 'Telegram привязан к этой карточке клиента.');
    }

    /** @return array<string, mixed> */
    private function validateClient(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'telegram_user_id' => ['nullable', 'integer'],
            'telegram_chat_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:active,paused,completed,archived'],
            'notifications_enabled' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]) + ['notifications_enabled' => $request->boolean('notifications_enabled')];
    }

    private function ensureProjectClient(Request $request, RentalClient $client): void
    {
        if ($client->project_id !== $request->attributes->get('project')->id) {
            abort(404);
        }
    }
}
