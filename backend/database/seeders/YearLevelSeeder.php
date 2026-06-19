<?php

namespace Database\Seeders;

use App\Models\YearLevel;
use Illuminate\Database\Seeder;

class YearLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['description' => 'Foundation', 'sort_order' => 0],
            ['description' => 'Year 1',     'sort_order' => 1],
            ['description' => 'Year 2',     'sort_order' => 2],
            ['description' => 'Year 3',     'sort_order' => 3],
            ['description' => 'Year 4',     'sort_order' => 4],
            ['description' => 'Year 5',     'sort_order' => 5],
            ['description' => 'Year 6',     'sort_order' => 6],
            ['description' => 'Year 7',     'sort_order' => 7],
            ['description' => 'Year 8',     'sort_order' => 8],
            ['description' => 'Year 9',     'sort_order' => 9],
            ['description' => 'Year 10',    'sort_order' => 10],
            ['description' => 'Year 11',    'sort_order' => 11],
            ['description' => 'Year 12',    'sort_order' => 12],
        ];

        foreach ($levels as $level) {
            YearLevel::create($level);
        }
    }
}
