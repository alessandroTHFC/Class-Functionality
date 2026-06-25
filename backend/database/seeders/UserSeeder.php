<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(string $emailDomain = 'demo.com', array $names = []): void
    {
        // Fall back to generic names if none provided for this tenant.
        $names = array_merge([
            'admin'     => 'Admin User',
            'coord'     => 'Jane Coordinator',
            'teacher'   => 'John Teacher',
            'assistant' => 'Sarah Assistant',
            'readonly'  => 'Read Only User',
        ], $names);

        $users = [
            ['name' => $names['admin'],     'email' => "admin@{$emailDomain}",       'role' => 'school-admin'],
            ['name' => $names['coord'],     'email' => "coordinator@{$emailDomain}",  'role' => 'coordinator'],
            ['name' => $names['teacher'],   'email' => "teacher@{$emailDomain}",      'role' => 'teacher'],
            ['name' => $names['assistant'], 'email' => "assistant@{$emailDomain}",    'role' => 'teachers-assistant'],
            ['name' => $names['readonly'],  'email' => "readonly@{$emailDomain}",     'role' => 'read-only'],
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
