<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use App\Models\Project;
use Illuminate\Database\Seeder;

class LeadOperatorStatusSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->where('slug', 'pikhlak')->first();
        if (! $project) {
            return;
        }

        LeadStatus::query()->updateOrCreate(
            ['project_id' => $project->id, 'code' => 'operator'],
            [
                'name' => 'Ждёт оператора',
                'sort' => 5,
                'color' => '#f97316',
                'is_won' => false,
                'is_lost' => false,
            ]
        );
    }
}
