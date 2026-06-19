<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentNote>
 */
class StudentNoteFactory extends Factory
{
    protected $model = StudentNote::class;

    public function definition(): array
    {
        return [
            'student_id'            => Student::factory(),
            'class_id'              => SchoolClass::factory(),
            'user_id'               => User::factory(),
            'note_text'             => $this->faker->paragraph(),
            'note_date'             => $this->faker->dateTimeBetween('-1 year', 'now'),
            'confidentiality_level' => $this->faker->optional()->randomElement(['low', 'high']),
        ];
    }
}
