<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Project;
use Illuminate\Database\Seeder;

class CarsSampleSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->where('slug', config('pikhlak.default_project_slug', 'pikhlak'))->first();
        if (! $project) {
            return;
        }

        $samples = [
            [
                'make' => 'Kia',
                'model' => 'K5',
                'year' => 2021,
                'price' => 18500,
                'description' => 'Kia K5 в отличном состоянии, полная комплектация GT Line. Доставка под ключ из США.',
                'specs' => ['mileage' => '42 000 km', 'engine' => '1.6 Turbo', 'transmission' => 'Автомат'],
            ],
            [
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2020,
                'price' => 21900,
                'description' => 'Toyota Camry SE, один владелец, сервисная история. Идеален для города.',
                'specs' => ['mileage' => '58 000 km', 'engine' => '2.5', 'transmission' => 'Автомат'],
            ],
            [
                'make' => 'BMW',
                'model' => 'X5',
                'year' => 2019,
                'price' => 38500,
                'description' => 'BMW X5 xDrive40i, панорама, кожа, адаптивная подвеска.',
                'specs' => ['mileage' => '71 000 km', 'engine' => '3.0', 'transmission' => 'Автомат'],
            ],
        ];

        foreach ($samples as $data) {
            Car::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'make' => $data['make'],
                    'model' => $data['model'],
                    'year' => $data['year'],
                ],
                array_merge($data, [
                    'currency' => 'USD',
                    'status' => 'published',
                    'published_at' => now(),
                ])
            );
        }
    }
}
