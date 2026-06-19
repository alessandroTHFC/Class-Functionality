<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(string $emailDomain = 'demo.com'): void
    {
        $coordinator = User::where('email', "coordinator@{$emailDomain}")->first();
        $teacher     = User::where('email', "teacher@{$emailDomain}")->first();
        $assistant   = User::where('email', "assistant@{$emailDomain}")->first();
        $students    = Student::all();
        $yearLevels  = YearLevel::pluck('id', 'description');

        $classes = [
            [
                'name'          => 'Year 9 Science',
                'year_level_id' => $yearLevels['Year 9'],
                'staff'         => [$teacher->id, $coordinator->id],
                'students'      => $students->random(10)->pluck('id')->toArray(),
            ],
            [
                'name'          => 'Year 10 Mathematics',
                'year_level_id' => $yearLevels['Year 10'],
                'staff'         => [$teacher->id],
                'students'      => $students->random(8)->pluck('id')->toArray(),
            ],
            [
                'name'          => 'Year 7 English',
                'year_level_id' => $yearLevels['Year 7'],
                'staff'         => [$coordinator->id, $assistant->id],
                'students'      => $students->random(12)->pluck('id')->toArray(),
            ],
            [
                'name'          => 'Year 8 History',
                'year_level_id' => $yearLevels['Year 8'],
                'staff'         => [$teacher->id],
                'students'      => $students->random(9)->pluck('id')->toArray(),
            ],
        ];

        foreach ($classes as $data) {
            $class = SchoolClass::create([
                'name'               => $data['name'],
                'year_level_id'      => $data['year_level_id'],
                'created_by_user_id' => $coordinator->id,
            ]);

            $class->users()->sync($data['staff']);
            $class->students()->sync($data['students']);
        }
    }
}
