<?php

namespace Database\Seeders;

use App\Models\Bot;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Bot\BotMessageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PikhlakSeeder extends Seeder
{
    public function run(): void
    {
        $botSecret = config('pikhlak.bot_hmac_secret', 'pikhlak-bot-dev-secret-change-me');

        $project = Project::query()->firstOrCreate(
            ['slug' => config('pikhlak.default_project_slug', 'pikhlak')],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Pikhlak Auto',
                'settings' => [
                    'locale' => 'uk',
                    'timezone' => 'Europe/Kyiv',
                ],
                'is_active' => true,
            ]
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@pikhlak.local'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Pikhlak Admin',
                'password' => Hash::make('password'),
                'locale' => 'uk',
                'is_active' => true,
            ]
        );

        $role = Role::query()->firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
        );

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $admin->id,
            ],
            []
        );

        DB::table('project_user')->updateOrInsert(
            [
                'project_id' => $project->id,
                'user_id' => $admin->id,
            ],
            ['role' => 'owner', 'created_at' => now(), 'updated_at' => now()]
        );

        $bot = Bot::query()->firstOrCreate(
            ['project_id' => $project->id, 'type' => 'warming'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Pikhlak Warming Bot',
                'mode' => 'webhook',
                'webhook_secret' => 'pikhlak-webhook-secret-dev',
                'api_key_hash' => hash('sha256', $botSecret),
                'config' => ['language' => 'uk'],
                'is_active' => true,
            ]
        );

        $statuses = [
            ['code' => 'operator', 'name' => 'Ждёт оператора', 'sort' => 5, 'color' => '#f97316', 'is_won' => false, 'is_lost' => false],
            ['code' => 'new', 'name' => 'Новий', 'sort' => 10, 'color' => '#3b82f6', 'is_won' => false, 'is_lost' => false],
            ['code' => 'contacted', 'name' => 'На зв\'язку', 'sort' => 20, 'color' => '#8b5cf6', 'is_won' => false, 'is_lost' => false],
            ['code' => 'qualified', 'name' => 'Кваліфікований', 'sort' => 30, 'color' => '#06b6d4', 'is_won' => false, 'is_lost' => false],
            ['code' => 'proposal', 'name' => 'Пропозиція', 'sort' => 40, 'color' => '#f59e0b', 'is_won' => false, 'is_lost' => false],
            ['code' => 'won', 'name' => 'Угода', 'sort' => 50, 'color' => '#22c55e', 'is_won' => true, 'is_lost' => false],
            ['code' => 'lost', 'name' => 'Втрачено', 'sort' => 60, 'color' => '#ef4444', 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($statuses as $status) {
            LeadStatus::query()->updateOrCreate(
                ['project_id' => $project->id, 'code' => $status['code']],
                $status
            );
        }

        Setting::setValue($project->id, BotMessageService::KEY, app(BotMessageService::class)->defaults());

        $this->command?->info('Pikhlak seeded.');
        $this->command?->info("Project ID: {$project->id} | slug: {$project->slug}");
        $this->command?->info("Bot UUID: {$bot->uuid}");
        $this->command?->info('Admin: admin@pikhlak.local / password');
        $this->command?->info("Bot HMAC secret (env PIKHLAK_BOT_HMAC_SECRET): {$botSecret}");
    }
}
