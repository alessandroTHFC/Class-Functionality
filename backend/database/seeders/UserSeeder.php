<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(string $emailDomain = 'demo.com'): void
    {
        $users = [
            ['name' => 'Admin User',       'email' => "admin@{$emailDomain}",       'role' => 'school-admin'],
            ['name' => 'Jane Coordinator', 'email' => "coordinator@{$emailDomain}",  'role' => 'coordinator'],
            ['name' => 'John Teacher',     'email' => "teacher@{$emailDomain}",      'role' => 'teacher'],
            ['name' => 'Sarah Assistant',  'email' => "assistant@{$emailDomain}",    'role' => 'teachers-assistant'],
            ['name' => 'Read Only User',   'email' => "readonly@{$emailDomain}",     'role' => 'read-only'],
        ];

        foreach ($users as $data) {
            $user = User::factory()->create([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            $user->assignRole($data['role']);
        }
    }
}
