<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'name'               => 'Year ' . $this->faker->numberBetween(1, 12) . ' ' . $this->faker->word(),
            'year_level_id'      => YearLevel::factory(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
