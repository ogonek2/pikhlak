<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAllowedTopic;
use App\Models\AiForbiddenTopic;
use App\Models\AiModel;
use App\Models\AiProfile;
use App\Models\AiPromptRule;
use App\Models\AiResponseTemplate;
use App\Models\AiSystemPrompt;
use App\Models\AiWarmingScenario;
use App\Models\Project;
use App\Models\AiRoute;
use App\Services\AI\AiKernel;
use App\Services\AI\AiPipelineNormalizer;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\SettingHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiControlController extends Controller
{
    public function index(Request $request, AiProviderFactory $providers): View
    {
        $profile = $this->profile($request);
        $groq = app(\App\Services\AI\Providers\GroqProvider::class)->isConfigured();
        $gemini = app(\App\Services\AI\Providers\GeminiProvider::class)->isConfigured();

        return view('admin.ai.index', [
            'profile' => $profile,
            'providers' => [
                'groq' => $groq,
                'gemini' => $gemini,
                'default' => config('ai.default_provider'),
            ],
            'behavior' => SettingHelper::behavior($this->project($request)->id),
        ]);
    }

    public function settings(Request $request): View
    {
        return view('admin.ai.settings', [
            'profile' => $this->profile($request),
            'models' => AiModel::query()->where('is_active', true)->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'name' => ['required', 'string'],
            'model_id' => ['nullable', 'exists:ai_models,id'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:100', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['nullable', 'string'],
            'tone' => ['nullable', 'string'],
            'company_info' => ['nullable', 'string'],
            'sales_instructions' => ['nullable', 'string'],
        ]);

        $profile->update([
            'name' => $data['name'],
            'model_id' => $data['model_id'],
            'temperature' => $data['temperature'],
            'max_tokens' => $data['max_tokens'],
            'is_active' => $request->boolean('is_active'),
            'personality' => [
                'role' => $data['role'] ?? '',
                'tone' => $data['tone'] ?? '',
                'company_info' => $data['company_info'] ?? '',
                'sales_instructions' => $data['sales_instructions'] ?? '',
            ],
        ]);

        return back()->with('success', 'AI-профиль сохранён.');
    }

    public function prompts(Request $request): View
    {
        $profile = $this->profile($request);

        return view('admin.ai.prompts', [
            'profile' => $profile,
            'prompts' => $profile->prompts()->orderByDesc('version')->get(),
        ]);
    }

    public function storePrompt(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validate(['content' => ['required', 'string']]);
        $version = (int) $profile->prompts()->max('version') + 1;
        $profile->prompts()->create([
            'version' => $version,
            'content' => $data['content'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "Промпт v{$version} создан.");
    }

    public function publishPrompt(AiSystemPrompt $prompt): RedirectResponse
    {
        $prompt->profile->prompts()->update(['is_published' => false, 'published_at' => null]);
        $prompt->update(['is_published' => true, 'published_at' => now()]);

        return back()->with('success', 'Промпт опубликован.');
    }

    public function rules(Request $request): View
    {
        $profile = $this->profile($request);

        return view('admin.ai.rules', [
            'profile' => $profile,
            'rules' => $profile->promptRules()->orderByDesc('priority')->get(),
            'ruleTypes' => config('ai.rule_types', []),
            'rulePresets' => config('ai.rule_presets', []),
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $this->validateRule($request);
        $profile->promptRules()->create($data);

        return back()->with('success', 'Правило добавлено.');
    }

    public function updateRule(Request $request, AiPromptRule $rule): RedirectResponse
    {
        $this->ensureProfileRule($request, $rule);
        $rule->update($this->validateRule($request));

        return back()->with('success', 'Правило обновлено.');
    }

    public function storeRulePreset(Request $request, string $preset): RedirectResponse
    {
        $profile = $this->profile($request);
        $presets = config('ai.rule_presets', []);
        if (! isset($presets[$preset])) {
            return back()->with('error', 'Неизвестный пресет.');
        }

        $p = $presets[$preset];
        $profile->promptRules()->create([
            'name' => $p['name'],
            'type' => $p['type'],
            'priority' => (int) ($p['priority'] ?? 50),
            'instruction' => $p['instruction'],
            'is_active' => true,
            'condition' => [
                'always' => (bool) ($p['always'] ?? false),
                'keywords' => $this->parseKeywords($p['keywords'] ?? ''),
            ],
        ]);

        return back()->with('success', 'Пресет «'.$p['name'].'» добавлен.');
    }

    public function destroyRule(AiPromptRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('success', 'Правило удалено.');
    }

    public function storeRoutePreset(Request $request, string $preset): RedirectResponse
    {
        $project = $this->project($request);
        $presets = config('ai.route_presets', []);
        if (! isset($presets[$preset])) {
            return back()->with('error', 'Неизвестный пресет маршрута.');
        }

        $p = $presets[$preset];
        $profile = $this->profile($request);

        AiRoute::query()->updateOrCreate(
            ['project_id' => $project->id, 'slug' => $p['slug']],
            [
                'name' => $p['name'],
                'intent_keywords' => $this->parseKeywords($p['intent_keywords'] ?? ''),
                'extra_instruction' => $p['extra_instruction'] ?? '',
                'priority' => (int) ($p['priority'] ?? 50),
                'data_sources' => $p['data_sources'] ?? ['cars', 'faq'],
                'profile_id' => $profile->id,
                'is_active' => true,
            ]
        );

        $route = AiRoute::query()
            ->where('project_id', $project->id)
            ->where('slug', $p['slug'])
            ->first();

        return redirect()
            ->route('admin.ai.routes.edit', $route)
            ->with('success', 'Маршрут «'.$p['name'].'» создан — проверьте настройки.');
    }

    public function topics(Request $request): View
    {
        $profile = $this->profile($request);

        return view('admin.ai.topics', [
            'profile' => $profile,
            'allowed' => $profile->allowedTopics,
            'forbidden' => $profile->forbiddenTopics,
        ]);
    }

    public function storeAllowedTopic(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'topic' => ['required', 'string'],
            'keywords' => ['nullable', 'string'],
        ]);
        $profile->allowedTopics()->create([
            'topic' => $data['topic'],
            'keywords' => $this->parseKeywords($data['keywords'] ?? ''),
        ]);

        return back()->with('success', 'Разрешённая тема добавлена.');
    }

    public function storeForbiddenTopic(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'topic' => ['required', 'string'],
            'keywords' => ['nullable', 'string'],
            'action' => ['required', 'in:block,fallback,escalate'],
        ]);
        $profile->forbiddenTopics()->create([
            'topic' => $data['topic'],
            'keywords' => $this->parseKeywords($data['keywords'] ?? ''),
            'action' => $data['action'],
        ]);

        return back()->with('success', 'Запрещённая тема добавлена.');
    }

    public function destroyAllowedTopic(AiAllowedTopic $topic): RedirectResponse
    {
        $topic->delete();

        return back()->with('success', 'Удалено.');
    }

    public function destroyForbiddenTopic(AiForbiddenTopic $topic): RedirectResponse
    {
        $topic->delete();

        return back()->with('success', 'Удалено.');
    }

    public function templates(Request $request): View
    {
        $profile = $this->profile($request);

        return view('admin.ai.templates', [
            'profile' => $profile,
            'templates' => $profile->templates,
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'code' => ['required', 'string'],
            'template' => ['required', 'string'],
            'locale' => ['required', 'string'],
        ]);
        $profile->templates()->updateOrCreate(
            ['code' => $data['code'], 'locale' => $data['locale']],
            ['template' => $data['template']]
        );

        return back()->with('success', 'Шаблон сохранён.');
    }

    public function warming(Request $request): View
    {
        $profile = $this->profile($request);
        $scenario = $profile->warmingScenarios()->first();

        return view('admin.ai.warming', compact('profile', 'scenario'));
    }

    public function updateWarming(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'name' => ['required', 'string'],
            'steps_json' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $steps = json_decode($data['steps_json'], true);
        if (! is_array($steps)) {
            return back()->withErrors(['steps_json' => 'Некорректный JSON массива шагов.']);
        }

        AiWarmingScenario::query()->updateOrCreate(
            ['profile_id' => $profile->id, 'name' => $data['name']],
            [
                'steps' => $steps,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return back()->with('success', 'Сценарий прогрева сохранён.');
    }

    public function filters(Request $request): View
    {
        $project = $this->project($request);

        return view('admin.ai.filters', [
            'behavior' => SettingHelper::behavior($project->id),
        ]);
    }

    public function updateFilters(Request $request): RedirectResponse
    {
        $project = $this->project($request);
        $data = $request->validate([
            'auto_reply' => ['sometimes', 'boolean'],
            'use_faq_first' => ['sometimes', 'boolean'],
            'faq_match_threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_context_messages' => ['nullable', 'integer', 'min:3', 'max:30'],
            'enable_warming' => ['sometimes', 'boolean'],
            'hot_lead_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'warming_aggressiveness' => ['nullable', 'in:low,medium,high'],
        ]);

        SettingHelper::saveBehavior($project->id, [
            'auto_reply' => $request->boolean('auto_reply'),
            'use_faq_first' => $request->boolean('use_faq_first'),
            'use_project_data' => $request->boolean('use_project_data'),
            'faq_match_threshold' => (float) ($data['faq_match_threshold'] ?? 0.45),
            'max_context_messages' => (int) ($data['max_context_messages'] ?? 12),
            'enable_warming' => $request->boolean('enable_warming'),
            'hot_lead_threshold' => (int) ($data['hot_lead_threshold'] ?? 70),
            'warming_aggressiveness' => $data['warming_aggressiveness'] ?? 'medium',
            'strict_allowed_topics' => $request->boolean('strict_allowed_topics'),
            'disable_ai_on_operator_request' => $request->boolean('disable_ai_on_operator_request'),
        ]);

        return back()->with('success', 'Фильтры и поведение AI обновлены.');
    }

    public function playground(Request $request): View
    {
        return view('admin.ai.playground', ['profile' => $this->profile($request)]);
    }

    public function runPlayground(Request $request, AiKernel $kernel): RedirectResponse
    {
        $profile = $this->profile($request);
        $project = $this->project($request);
        $data = $request->validate(['message' => ['required', 'string']]);

        try {
            $reply = $kernel->processPlayground($project, $profile, $data['message']);
        } catch (\Throwable $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with([
            'success' => 'Ответ получен (через AI Kernel + базы данных).',
            'playground_reply' => $reply,
        ]);
    }

    public function models(Request $request): View
    {
        return view('admin.ai.models', [
            'models' => AiModel::query()->orderBy('provider')->orderBy('model_name')->get(),
            'providerLabels' => config('ai.provider_labels', []),
        ]);
    }

    public function storeModel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:groq,gemini,openai'],
            'model_name' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        AiModel::query()->create([
            'provider' => $data['provider'],
            'model_name' => $data['model_name'],
            'config' => ['label' => $data['label'] ?? $data['model_name']],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Модель ИИ добавлена.');
    }

    public function updateModel(Request $request, AiModel $model): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:groq,gemini,openai'],
            'model_name' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $model->update([
            'provider' => $data['provider'],
            'model_name' => $data['model_name'],
            'config' => array_merge($model->config ?? [], ['label' => $data['label'] ?? $data['model_name']]),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Модель обновлена.');
    }

    public function routes(Request $request): View
    {
        $project = $this->project($request);
        $search = trim((string) $request->query('q', ''));
        $active = $request->query('active');

        $query = AiRoute::query()
            ->with(['model', 'profile'])
            ->where('project_id', $project->id);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('extra_instruction', 'like', $like)
                    ->orWhere('intent_keywords', 'like', $like);
            });
        }

        if ($active === '1' || $active === '0') {
            $query->where('is_active', $active === '1');
        }

        return view('admin.ai.routes.index', [
            'routes' => $query->orderByDesc('priority')->orderBy('name')->paginate(25)->withQueryString(),
            'search' => $search,
            'active' => $active,
            'routePresets' => config('ai.route_presets', []),
            'totalCount' => AiRoute::query()->where('project_id', $project->id)->count(),
        ]);
    }

    public function createRoute(Request $request): View
    {
        return view('admin.ai.routes.create', $this->routeFormViewData($request, new AiRoute([
            'priority' => 60,
            'is_active' => true,
            'slug' => '',
        ])));
    }

    public function editRoute(Request $request, AiRoute $route): View
    {
        $this->ensureProjectRoute($request, $route);

        return view('admin.ai.routes.edit', $this->routeFormViewData($request, $route));
    }

    public function storeRoute(Request $request): RedirectResponse
    {
        $project = $this->project($request);
        $data = $this->validateRoute($request);
        $data['project_id'] = $project->id;
        $data['intent_keywords'] = $this->parseKeywords($data['intent_keywords_raw'] ?? '');
        $data['data_sources'] = $request->input('data_sources', []);
        unset($data['intent_keywords_raw'], $data['pipeline_json']);
        $route = AiRoute::query()->create($data);

        return redirect()
            ->route('admin.ai.routes.edit', $route)
            ->with('success', 'Маршрут ИИ создан.');
    }

    public function updateRoute(Request $request, AiRoute $route): RedirectResponse
    {
        $this->ensureProjectRoute($request, $route);
        $data = $this->validateRoute($request);
        $data['intent_keywords'] = $this->parseKeywords($data['intent_keywords_raw'] ?? '');
        $data['data_sources'] = $request->input('data_sources', []);
        unset($data['intent_keywords_raw'], $data['pipeline_json']);
        $route->update($data);

        return redirect()
            ->route('admin.ai.routes.edit', $route)
            ->with('success', 'Маршрут обновлён.');
    }

    public function destroyRoute(Request $request, AiRoute $route): RedirectResponse
    {
        $this->ensureProjectRoute($request, $route);

        if ($route->slug === 'default') {
            return redirect()
                ->route('admin.ai.routes')
                ->withErrors(['route' => 'Маршрут default нельзя удалить.']);
        }

        $route->delete();

        return redirect()
            ->route('admin.ai.routes')
            ->with('success', 'Маршрут удалён.');
    }

    /** @return array<string, mixed> */
    private function routeFormViewData(Request $request, AiRoute $route): array
    {
        $project = $this->project($request);

        return [
            'route' => $route,
            'models' => AiModel::query()->where('is_active', true)->get(),
            'profiles' => AiProfile::query()->where('project_id', $project->id)->get(),
            'routePresets' => config('ai.route_presets', []),
        ];
    }

    private function ensureProjectRoute(Request $request, AiRoute $route): void
    {
        if ($route->project_id !== $this->project($request)->id) {
            abort(404);
        }
    }

    private function validateRoute(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:60', 'alpha_dash'],
            'intent_keywords_raw' => ['nullable', 'string'],
            'model_id' => ['nullable', 'exists:ai_models,id'],
            'profile_id' => ['nullable', 'exists:ai_profiles,id'],
            'extra_instruction' => ['nullable', 'string'],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'pipeline_json' => ['nullable', 'string'],
        ]) + [
            'is_active' => $request->has('is_active'),
            'pipeline' => $this->parsePipeline($request->input('pipeline_json')),
        ];
    }

    private function parsePipeline(?string $json): ?array
    {
        return AiPipelineNormalizer::fromJson($json);
    }

    private function profile(Request $request): AiProfile
    {
        $project = $this->project($request);

        return AiProfile::query()->firstOrCreate(
            ['project_id' => $project->id, 'is_default' => true],
            [
                'name' => 'Pikhlak Lead Warming AI',
                'temperature' => 0.65,
                'max_tokens' => 800,
                'is_active' => true,
                'personality' => [
                    'role' => 'Консультант автосалона Pikhlak Auto',
                    'tone' => 'дружелюбный, экспертный',
                    'company_info' => 'Pikhlak — подбор и доставка авто под ключ из США/Европы.',
                    'sales_instructions' => 'Выясняй бюджет, марку, срок. Предлагай калькулятор. Не дави.',
                ],
            ]
        );
    }

    private function project(Request $request): Project
    {
        return $request->attributes->get('project');
    }

    private function parseKeywords(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $raw) ?: [])));
    }

    private function validateRule(Request $request): array
    {
        $typeKeys = array_keys(config('ai.rule_types', []));
        if ($typeKeys === []) {
            $typeKeys = ['instruction', 'constraint', 'example', 'company_fact', 'correction'];
        }
        $types = implode(',', $typeKeys);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'type' => ['required', 'in:'.$types],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'instruction' => ['required', 'string', 'max:8000'],
            'trigger_keywords_raw' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'name' => $data['name'] ?? null,
            'type' => $data['type'],
            'priority' => (int) $data['priority'],
            'instruction' => $data['instruction'],
            'is_active' => $request->boolean('is_active', true),
            'condition' => [
                'always' => $request->boolean('always_apply'),
                'keywords' => $this->parseKeywords($data['trigger_keywords_raw'] ?? ''),
            ],
        ];
    }

    private function ensureProfileRule(Request $request, AiPromptRule $rule): void
    {
        $profile = $this->profile($request);
        if ($rule->profile_id !== $profile->id) {
            abort(404);
        }
    }
}
