<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\RentalClient;
use App\Models\RentalClientContract;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use App\Models\RentalClientPhone;
use App\Models\RentalClientVehicle;
use App\Models\TrafficChannel;
use App\Models\TrafficChannelStat;
use App\Services\Bot\BotRegistry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClientPortalSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->where('slug', config('pikhlak.default_project_slug', 'pikhlak'))->first();
        if (! $project) {
            return;
        }

        app(BotRegistry::class)->client($project);

        foreach (config('client_portal.demo_channels', []) as $preset) {
            $channel = TrafficChannel::query()->updateOrCreate(
                ['project_id' => $project->id, 'slug' => $preset['slug']],
                [
                    'name' => $preset['name'],
                    'is_active' => true,
                    'api_connected' => false,
                ]
            );

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->toDateString();
                $clicks = random_int($preset['clicks_min'] ?? 20, $preset['clicks_max'] ?? 80);
                TrafficChannelStat::query()->updateOrCreate(
                    ['traffic_channel_id' => $channel->id, 'stat_date' => $date],
                    [
                        'impressions' => $clicks * random_int(8, 14),
                        'clicks' => $clicks,
                        'leads' => random_int($preset['leads_min'] ?? 1, $preset['leads_max'] ?? 5),
                        'spend' => round($clicks * ($preset['cpc'] ?? 0.4), 2),
                        'revenue' => random_int(0, 3) * ($preset['revenue_per_lead'] ?? 100),
                    ]
                );
            }
        }

        if (RentalClient::query()->where('project_id', $project->id)->exists()) {
            return;
        }

        $samples = [
            [
                'name' => 'Олег Коваленко',
                'phone' => '+380501112233',
                'make' => 'Hyundai', 'model' => 'Sonata', 'year' => 2020, 'plate' => 'AA1234BB',
                'monthly' => 450, 'rent_end' => now()->addMonths(8),
            ],
            [
                'name' => 'Марина Петренко',
                'phone' => '+380671234567',
                'make' => 'Kia', 'model' => 'K5', 'year' => 2021, 'plate' => 'KA5678CH',
                'monthly' => 520, 'rent_end' => now()->addMonths(14),
            ],
            [
                'name' => 'Игорь Сидоренко',
                'phone' => '+380931112233',
                'make' => 'Toyota', 'model' => 'Camry', 'year' => 2019, 'plate' => 'BI9012AK',
                'monthly' => 480, 'rent_end' => now()->addMonths(5),
            ],
        ];

        $botId = app(BotRegistry::class)->client($project)->id;

        foreach ($samples as $index => $s) {
            $client = RentalClient::query()->create([
                'project_id' => $project->id,
                'bot_id' => $botId,
                'crm_external_id' => 1000 + $index + 1,
                'link_token' => Str::lower(Str::random(10)),
                'full_name' => $s['name'],
                'status' => 'active',
                'notifications_enabled' => true,
            ]);

            RentalClientPhone::query()->create([
                'rental_client_id' => $client->id,
                'label' => 'mobile',
                'phone' => $s['phone'],
                'is_primary' => true,
            ]);

            $vehicle = RentalClientVehicle::query()->create([
                'rental_client_id' => $client->id,
                'make' => $s['make'],
                'model' => $s['model'],
                'year' => $s['year'],
                'plate_number' => $s['plate'],
                'mileage' => random_int(45000, 120000),
                'is_current' => true,
            ]);

            RentalClientContract::query()->create([
                'rental_client_id' => $client->id,
                'rental_client_vehicle_id' => $vehicle->id,
                'contract_number' => 'PK-2024-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'rent_start' => now()->subMonths(4),
                'rent_end' => $s['rent_end'],
                'monthly_amount' => $s['monthly'],
                'currency' => 'UAH',
                'total_amount' => $s['monthly'] * 24,
                'buyout_option' => true,
                'status' => 'active',
            ]);

            RentalClientInsurance::query()->create([
                'rental_client_id' => $client->id,
                'provider' => 'ASKO',
                'valid_from' => now()->subMonths(2),
                'valid_until' => now()->addMonths(10),
                'premium_amount' => 890,
            ]);

            RentalClientMaintenance::query()->create([
                'rental_client_id' => $client->id,
                'rental_client_vehicle_id' => $vehicle->id,
                'type' => 'service',
                'title' => 'Плановое ТО',
                'scheduled_at' => now()->addDays(random_int(5, 20)),
                'status' => 'planned',
            ]);

            RentalClientPayment::query()->create([
                'rental_client_id' => $client->id,
                'type' => 'rent',
                'amount' => $index === 0 ? 12500 : $s['monthly'],
                'due_date' => $index === 0 ? now()->addDays(5) : now()->startOfMonth()->addDays(14),
                'status' => 'pending',
            ]);
        }
    }
}
