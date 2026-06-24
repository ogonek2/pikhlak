<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\AiProfile;
use App\Models\AiRoute;
use App\Models\Project;
use Illuminate\Database\Seeder;

class AiRoutesSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->where('slug', config('pikhlak.default_project_slug', 'pikhlak'))->first();
        if (! $project) {
            return;
        }

        $profile = AiProfile::query()->where('project_id', $project->id)->where('is_default', true)->first();
        $groq = AiModel::query()->where('provider', 'groq')->first();
        $gemini = AiModel::query()->where('provider', 'gemini')->first();

        $routes = [
            [
                'slug' => 'warming',
                'name' => 'Прогрев лида',
                'intent_keywords' => ['купить', 'хочу', 'нужен', 'нужна', 'интерес', 'бюджет', 'срок', 'достав'],
                'model_id' => $groq?->id,
                'profile_id' => $profile?->id,
                'data_sources' => ['cars', 'faq', 'leads'],
                'extra_instruction' => 'Выясни бюджет, марку и срок. Предложи подходящие авто из каталога. При явном интересе предложи связь с менеджером.',
                'priority' => 80,
            ],
            [
                'slug' => 'catalog',
                'name' => 'Каталог / наличие',
                'intent_keywords' => ['каталог', 'налич', 'есть ли', 'покажи', 'авто в', 'машин'],
                'model_id' => $gemini?->id ?? $groq?->id,
                'profile_id' => $profile?->id,
                'data_sources' => ['cars', 'faq'],
                'extra_instruction' => 'Отвечай только по авто из каталога. Указывай ID, цену, статус. Если нет — предложи подбор.',
                'priority' => 90,
            ],
            [
                'slug' => 'calculator',
                'name' => 'Аренда с выкупом',
                'intent_keywords' => ['расчёт', 'калькулятор', 'первый взнос', 'право выкупа', 'сколько платить', 'в неделю'],
                'model_id' => $groq?->id,
                'profile_id' => $profile?->id,
                'data_sources' => ['cars', 'faq', 'leads'],
                'extra_instruction' => 'Для расчёта аренды с правом выкупа предложи написать «калькулятор». Не выдумывай цифры и не объясняй формулу — расчёт делает система.',
                'priority' => 85,
            ],
            [
                'slug' => 'not_leasing',
                'name' => 'Уточнение: не лизинг',
                'intent_keywords' => ['лизинг', 'аренда', 'leasing', 'в аренду', 'арендовать'],
                'model_id' => $groq?->id,
                'profile_id' => $profile?->id,
                'data_sources' => ['cars', 'faq'],
                'extra_instruction' => 'Клиент думает, что мы лизинг. Объясни: Pikhlak Auto = покупка + доставка авто под ключ. Не лизинг. Предложи каталог или менеджера.',
                'priority' => 95,
            ],
            [
                'slug' => 'about_company',
                'name' => 'О компании',
                'intent_keywords' => ['кто вы', 'что за компания', 'чем занимаетесь', 'услуги', 'pikhlak', 'пихлак'],
                'model_id' => $groq?->id,
                'profile_id' => $profile?->id,
                'data_sources' => ['faq', 'cars'],
                'extra_instruction' => 'Кратко опиши Pikhlak Auto: импорт авто, подбор, доставка, растаможка. Не лизинг.',
                'priority' => 75,
            ],
            [
                'slug' => 'default',
                'name' => 'По умолчанию',
                'intent_keywords' => [],
                'model_id' => $groq?->id,
                'profile_id' => $profile?->id,
                'data_sources' => ['cars', 'faq', 'leads'],
                'priority' => 1,
            ],
        ];

        foreach ($routes as $route) {
            AiRoute::query()->updateOrCreate(
                ['project_id' => $project->id, 'slug' => $route['slug']],
                array_merge($route, ['is_active' => true, 'pipeline' => null])
            );
        }
    }
}
