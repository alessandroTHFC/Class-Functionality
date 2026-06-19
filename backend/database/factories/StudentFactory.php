<?php

namespace Database\Factories;

use App\Enums\NccdCategoryEnum;
use App\Enums\NccdLevelEnum;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'given_name'                          => $this->faker->firstName(),
            'family_name'                         => $this->faker->lastName(),
            'date_of_birth'                       => $this->faker->dateTimeBetween('-18 years', '-5 years'),
            'nccd_level'                          => $this->faker->randomElement(NccdLevelEnum::cases())->value,
            'nccd_category'                       => $this->faker->randomElement(NccdCategoryEnum::cases())->value,
            'primary_disability'                  => $this->faker->optional()->word(),
            'primary_disability_level_formalised' => $this->faker->boolean(),
        ];
    }
}
