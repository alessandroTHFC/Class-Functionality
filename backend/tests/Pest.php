<?php

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

function actingAsRole(string $role): TestCase
{
    $user = User::factory()->create(['tenant_id' => test()->tenant->id]);
    $user->assignRole($role);

    return test()->actingAs($user, 'sanctum');
}
