<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\YearLevel;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $yearLevels = YearLevel::all();

        Student::factory(30)->make()->each(function (Student $student) use ($yearLevels) {
            $student->year_level_id = $yearLevels->random()->id;
            $student->save();
        });
    }
}
