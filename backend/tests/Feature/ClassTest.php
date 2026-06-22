<?php

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Models\YearLevel;

// Each describe() block groups tests for a single endpoint.
// actingAsRole() (defined in tests/Pest.php) creates a user, assigns the role, and logs them in via Sanctum.
// RefreshDatabase (on TestCase) wraps each test in a transaction and rolls it back — each test starts clean.

describe('GET /api/classes', function () {

    // Verifies the full response shape including the tenant-wide summary in meta.
    // assertJsonStructure() checks keys exist but not their values — used here to confirm
    // the ClassListCollection is injecting the summary into the paginated meta block.
    it('returns paginated classes for coordinator', function () {
        SchoolClass::factory()->count(3)->create();

        actingAsRole('coordinator')
            ->getJson('/api/classes')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta' => ['summary' => ['total_students', 'teachers_assigned']]]);
    });

    it('returns classes for teacher', function () {
        SchoolClass::factory()->count(2)->create();

        actingAsRole('teacher')
            ->getJson('/api/classes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns classes for read-only', function () {
        actingAsRole('read-only')
            ->getJson('/api/classes')
            ->assertOk();
    });

    // No token sent — Sanctum returns 401 before the controller is reached.
    it('returns 401 for unauthenticated request', function () {
        $this->getJson('/api/classes')->assertUnauthorized();
    });

    // The scopeSearch() query scope on SchoolClass does a LIKE filter on the name column.
    // assertJsonPath() drills into the response JSON using dot notation to check a specific value.
    it('filters by search term', function () {
        SchoolClass::factory()->create(['name' => 'Year 9 Science']);
        SchoolClass::factory()->create(['name' => 'Year 10 Maths']);

        actingAsRole('teacher')
            ->getJson('/api/classes?search=Science')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Year 9 Science');
    });

    it('filters by year_level_id', function () {
        $yearLevel = YearLevel::factory()->create();
        SchoolClass::factory()->create(['year_level_id' => $yearLevel->id]);
        SchoolClass::factory()->create(); // different year level — should be excluded

        actingAsRole('teacher')
            ->getJson("/api/classes?year_level_id={$yearLevel->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('POST /api/classes', function () {

    // assertDatabaseHas() bypasses the HTTP layer and queries the DB directly —
    // confirms the record was actually written, not just that the response said so.
    it('allows coordinator to create a class', function () {
        actingAsRole('coordinator')
            ->postJson('/api/classes', ['name' => 'Year 9 Science'])
            ->assertCreated()
            ->assertJson(['message' => 'Class created successfully.']);

        $this->assertDatabaseHas('classes', ['name' => 'Year 9 Science']);
    });

    it('allows teacher to create a class', function () {
        actingAsRole('teacher')
            ->postJson('/api/classes', ['name' => 'Year 8 English'])
            ->assertCreated();
    });

    // StoreClassRequest::authorize() returns false for this role → Laravel returns 403.
    it('returns 403 when teachers-assistant tries to create a class', function () {
        actingAsRole('teachers-assistant')
            ->postJson('/api/classes', ['name' => 'Year 7 History'])
            ->assertForbidden();
    });

    it('returns 403 when read-only tries to create a class', function () {
        actingAsRole('read-only')
            ->postJson('/api/classes', ['name' => 'Year 7 History'])
            ->assertForbidden();
    });

    // Validation runs after authorization — 422 means the request was authorised but invalid.
    it('returns 422 when name is missing', function () {
        actingAsRole('coordinator')
            ->postJson('/api/classes', [])
            ->assertUnprocessable();
    });

    // Verifies the sync() in ClassService::create() writes pivot rows to class_users.
    it('syncs user_ids into class_users when provided', function () {
        $user = User::factory()->create(['tenant_id' => test()->tenant->id]);

        actingAsRole('coordinator')
            ->postJson('/api/classes', [
                'name'     => 'Year 9 Science',
                'user_ids' => [$user->id],
            ])
            ->assertCreated();

        $class = SchoolClass::where('name', 'Year 9 Science')->first();
        $this->assertDatabaseHas('class_users', ['class_id' => $class->id, 'user_id' => $user->id]);
    });

    // Verifies the sync() in ClassService::create() writes pivot rows to class_students.
    it('syncs student_ids into class_students when provided', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        actingAsRole('coordinator')
            ->postJson('/api/classes', [
                'name'        => 'Year 9 Science',
                'student_ids' => [$student->id],
            ])
            ->assertCreated();

        $class = SchoolClass::where('name', 'Year 9 Science')->first();
        $this->assertDatabaseHas('class_students', ['class_id' => $class->id, 'student_id' => $student->id]);
    });

    // Verifies ClassObserver::creating() fires and sets created_by_user_id automatically.
    // We assert it is not null rather than a specific ID because actingAsRole() creates a
    // new user each time and we don't need to capture their exact ID for this assertion.
    it('sets created_by_user_id via the observer', function () {
        actingAsRole('coordinator')
            ->postJson('/api/classes', ['name' => 'Year 9 Science'])
            ->assertCreated();

        $class = SchoolClass::where('name', 'Year 9 Science')->first();
        $this->assertNotNull($class->created_by_user_id);
    });
});

describe('GET /api/classes/{class}', function () {

    // assertJsonStructure() confirms all expected keys are present in the response shape.
    // This is a contract test — it catches any resource field being accidentally removed.
    it('returns full class detail for coordinator', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('coordinator')
            ->getJson("/api/classes/{$class->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'year_level', 'created_by', 'assigned_users', 'nccd_summary', 'students'],
            ]);
    });

    it('returns full class detail for teacher', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('teacher')
            ->getJson("/api/classes/{$class->id}")
            ->assertOk();
    });

    it('returns full class detail for read-only', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('read-only')
            ->getJson("/api/classes/{$class->id}")
            ->assertOk();
    });

    it('returns 401 for unauthenticated request', function () {
        $class = SchoolClass::factory()->create();

        $this->getJson("/api/classes/{$class->id}")->assertUnauthorized();
    });

    // findOrFail() in the repository throws ModelNotFoundException → Laravel converts to 404.
    it('returns 404 for a non-existent class', function () {
        actingAsRole('coordinator')
            ->getJson('/api/classes/99999')
            ->assertNotFound();
    });
});

describe('PUT /api/classes/{class}', function () {

    // assertJsonPath() verifies the updated name is reflected in the response.
    // The controller returns ClassDetailResource on update — same shape as show.
    it('allows coordinator to update a class', function () {
        $class = SchoolClass::factory()->create(['name' => 'Old Name']);

        actingAsRole('coordinator')
            ->putJson("/api/classes/{$class->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    });

    it('allows teacher to update a class', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('teacher')
            ->putJson("/api/classes/{$class->id}", ['name' => 'Updated'])
            ->assertOk();
    });

    // UpdateClassRequest::authorize() checks 'edit class' — teachers-assistant lacks this.
    it('returns 403 when teachers-assistant tries to update', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('teachers-assistant')
            ->putJson("/api/classes/{$class->id}", ['name' => 'Updated'])
            ->assertForbidden();
    });

    it('returns 403 when read-only tries to update', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('read-only')
            ->putJson("/api/classes/{$class->id}", ['name' => 'Updated'])
            ->assertForbidden();
    });
});

describe('DELETE /api/classes/{class}', function () {

    it('allows coordinator to delete a class', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('coordinator')
            ->deleteJson("/api/classes/{$class->id}")
            ->assertOk()
            ->assertJson(['message' => 'Class deleted successfully.']);
    });

    // assertSoftDeleted() confirms deleted_at is set — the row still exists in the DB
    // but will be excluded from all Eloquent queries by the SoftDeletes global scope.
    it('soft-deletes — deleted_at is set, row still exists', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('coordinator')
            ->deleteJson("/api/classes/{$class->id}")
            ->assertOk();

        $this->assertSoftDeleted('classes', ['id' => $class->id]);
    });

    // ClassPolicy::delete() checks 'delete class' — teachers have 'edit class' but not 'delete class'.
    it('returns 403 when teacher tries to delete', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('teacher')
            ->deleteJson("/api/classes/{$class->id}")
            ->assertForbidden();
    });

    it('returns 403 when read-only tries to delete', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('read-only')
            ->deleteJson("/api/classes/{$class->id}")
            ->assertForbidden();
    });
});

describe('Tenant isolation', function () {

    // BelongsToTenant applies a global Eloquent scope that filters every query by the
    // current tenant_id. This test spins up a second tenant, creates a class there,
    // then verifies a user from the first tenant cannot see it.
    //
    // How it works:
    // 1. A class is created in the first tenant (already initialized by TestCase::setUp).
    // 2. Tenancy is ended, a second tenant is initialized, and a class is created there.
    // 3. Tenancy is ended and the first tenant is re-initialized.
    // 4. actingAsRole() creates a user with tenant_id = test()->tenant->id.
    // 5. The HTTP request hits InitialiseTenantFromUser, which re-initializes tenancy
    //    from the user's tenant_id — locking the query scope to tenant 1.
    // 6. BelongsToTenant filters the classes query — only tenant 1's class is returned.
    it('does not return classes belonging to another tenant', function () {
        $ownClass = SchoolClass::factory()->create(['name' => 'Tenant One Class']);

        // Temporarily switch to a second tenant and seed a class there
        tenancy()->end();
        $otherTenant = Tenant::factory()->create();
        tenancy()->initialize($otherTenant);
        SchoolClass::factory()->create(['name' => 'Tenant Two Class']);
        tenancy()->end();

        // Restore the first tenant context for the remainder of the test
        tenancy()->initialize(test()->tenant);

        actingAsRole('teacher')
            ->getJson('/api/classes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tenant One Class');
    });

    // Route model binding resolves {class} through BelongsToTenant's global scope.
    // A class that belongs to another tenant is invisible — findOrFail returns 404.
    it('returns 404 when accessing a class from another tenant directly', function () {
        tenancy()->end();
        $otherTenant = Tenant::factory()->create();
        tenancy()->initialize($otherTenant);
        $otherClass = SchoolClass::factory()->create();
        tenancy()->end();

        tenancy()->initialize(test()->tenant);

        actingAsRole('teacher')
            ->getJson("/api/classes/{$otherClass->id}")
            ->assertNotFound();
    });
});
