<?php

namespace Database\Factories;

use App\Models\YearLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YearLevel>
 */
class YearLevelFactory extends Factory
{
    protected $model = YearLevel::class;

    public function definition(): array
    {
        return [
            'description' => 'Year ' . $this->faker->numberBetween(1, 12),
            'sort_order'  => $this->faker->numberBetween(1, 12),
        ];
    }
}
