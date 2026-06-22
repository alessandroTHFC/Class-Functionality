<?php

use App\Models\SchoolClass;
use App\Models\User;

// These tests verify the staff sync behaviour that happens through PUT /api/classes/{class}.
// Staff assignments are managed exclusively through the update flow, same as student enrolment.

describe('Staff sync via PUT /api/classes/{class}', function () {

    // Creates a class with one user assigned, then updates with two users.
    // Verifies both are present in class_users after the sync.
    it('adds new staff when user_ids includes IDs not yet assigned', function () {
        $class        = SchoolClass::factory()->create();
        $existingUser = User::factory()->create(['tenant_id' => test()->tenant->id]);
        $newUser      = User::factory()->create(['tenant_id' => test()->tenant->id]);

        $class->users()->sync([$existingUser->id]);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", [
                'name'     => $class->name,
                'user_ids' => [$existingUser->id, $newUser->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('class_users', ['class_id' => $class->id, 'user_id' => $newUser->id]);
        $this->assertDatabaseHas('class_users', ['class_id' => $class->id, 'user_id' => $existingUser->id]);
    });

    // sync() removes pivot rows not in the submitted array — $existingUser is dropped here.
    it('removes staff omitted from the user_ids array', function () {
        $class        = SchoolClass::factory()->create();
        $existingUser = User::factory()->create(['tenant_id' => test()->tenant->id]);
        $newUser      = User::factory()->create(['tenant_id' => test()->tenant->id]);

        $class->users()->sync([$existingUser->id]);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", [
                'name'     => $class->name,
                'user_ids' => [$newUser->id],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('class_users', ['class_id' => $class->id, 'user_id' => $existingUser->id]);
        $this->assertDatabaseHas('class_users', ['class_id' => $class->id, 'user_id' => $newUser->id]);
    });

    // Submitting an empty array removes all assigned staff — sync([]) clears the pivot table.
    it('removes all staff when user_ids is an empty array', function () {
        $class = SchoolClass::factory()->create();
        $user  = User::factory()->create(['tenant_id' => test()->tenant->id]);

        $class->users()->sync([$user->id]);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", [
                'name'     => $class->name,
                'user_ids' => [],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('class_users', ['class_id' => $class->id, 'user_id' => $user->id]);
    });

    // read-only lacks 'edit class' permission — UpdateClassRequest::authorize() blocks them.
    it('returns 403 when read-only tries to update staff assignment', function () {
        $class = SchoolClass::factory()->create();
        $user  = User::factory()->create(['tenant_id' => test()->tenant->id]);

        actingAsRole('read-only')
            ->putJson("/api/classes/{$class->id}", [
                'name'     => $class->name,
                'user_ids' => [$user->id],
            ])
            ->assertForbidden();
    });
});
