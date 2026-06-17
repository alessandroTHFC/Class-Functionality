# Testing

## Approach

Tests are written **alongside each feature** as it is built — not at the end. After building and manually verifying an endpoint, write the Pest tests for it before moving on. This keeps test coverage current and edge cases fresh.

**Pest PHP** is the test framework. It ships with Laravel 11 by default. No additional installation needed.

The primary layer is **Feature Tests** — these hit the full HTTP stack (middleware, form requests, controllers, services, repositories) and assert on the JSON response. **Unit tests** are used selectively for service methods with non-trivial logic (e.g. bulk note creation).

---

## What We Test

| Layer | How |
|---|---|
| API endpoints | Feature tests — assert status codes and response shapes |
| Authorization | Feature tests — assert 403 for wrong role, 200 for correct role |
| Tenant isolation | Feature tests — assert tenant A cannot see tenant B's data |
| Bulk note logic | Unit test on `NoteService` |
| NCCD summary | Unit test on `ClassDetailResource` |

We do **not** test:
- Repositories in isolation (covered by feature tests)
- API Resources in isolation (covered by feature tests)
- Eloquent relationships (framework responsibility)

---

## Test Structure

```
tests/
├── Feature/
│   ├── AuthTest.php
│   ├── ClassTest.php
│   ├── ClassStudentTest.php
│   ├── ClassUserTest.php
│   ├── NoteTest.php
│   └── TenantIsolationTest.php
└── Unit/
    ├── NoteServiceTest.php
    └── ClassDetailResourceTest.php
```

---

## Factories

Every model has a factory. Factories generate realistic data without depending on seeders.

```
database/factories/
├── UserFactory.php
├── SchoolClassFactory.php
├── StudentFactory.php
├── StudentNoteFactory.php
└── YearLevelFactory.php
```

**Example — `StudentFactory`:**
```php
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'given_name'                          => $this->faker->firstName(),
            'family_name'                         => $this->faker->lastName(),
            'date_of_birth'                       => $this->faker->dateTimeBetween('-18 years', '-5 years'),
            'nccd_level'                          => $this->faker->randomElement(['QDTP', 'Supplementary', 'Substantial', 'Extensive']),
            'nccd_category'                       => $this->faker->randomElement(['Cognitive', 'Physical', 'Sensory', 'Social/Emotional']),
            'primary_disability'                  => $this->faker->optional()->word(),
            'primary_disability_level_formalised' => $this->faker->boolean(),
        ];
    }
}
```

---

## Base Test Setup — Tenancy Helper

Tests that hit tenant-scoped endpoints need a tenant initialised. Add a `TestCase` base that handles this so individual tests stay clean:

```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }
}
```

Add a helper in `tests/Pest.php` so any test can log in as a role in one call:

```php
// tests/Pest.php
uses(TestCase::class)->in('Feature');

function actingAsRole(string $role): TestCase
{
    $user = User::factory()->create(['tenant_id' => test()->tenant->id]);
    $user->assignRole($role);

    return test()->actingAs($user, 'sanctum');
}
```

Usage in any feature test:
```php
actingAsRole('coordinator')->getJson('/api/classes')->assertOk();
```

---

## Pest Conventions

### Structure — describe blocks group related tests

```php
describe('GET /api/classes', function () {

    it('returns classes for an authenticated user', function () {
        SchoolClass::factory()->count(3)->create();

        actingAsRole('teacher')
            ->getJson('/api/classes')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns 401 for an unauthenticated request', function () {
        $this->getJson('/api/classes')->assertUnauthorized();
    });

});
```

### Each test covers one behaviour — assert status first, then shape

```php
it('returns the correct response shape', function () {
    $class = SchoolClass::factory()->create();

    actingAsRole('coordinator')
        ->getJson("/api/classes/{$class->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'year_level', 'assigned_users', 'students'],
        ]);
});
```

### Minimal setup — only create what the test needs

```php
// Good
$class = SchoolClass::factory()->create();

// Bad — running the full seeder for a simple read test
$this->artisan('db:seed');
```

---

## AuthTest.php — Cases to Cover

```php
it('returns a token on successful login')
it('returns 401 for wrong password')
it('returns 401 for non-existent email')
it('revokes the token on logout')
it('returns 401 on a request after logout')
it('returns the authenticated user with roles and tenant on GET /api/user')
it('returns 401 on GET /api/user without a token')
```

---

## ClassTest.php — Cases to Cover

```php
// Listing
it('allows coordinator to list classes')
it('allows teacher to list classes')
it('allows read-only to list classes')

// Creating
it('allows coordinator to create a class')
it('allows teacher to create a class')
it('returns 403 when read-only tries to create a class')
it('returns 422 when name is missing')
it('creates class_user records when user_ids are provided')
it('creates class_student records when student_ids are provided')

// Viewing
it('allows coordinator to view any class')
it('allows teacher to view an assigned class')
it('returns 403 when teacher tries to view an unassigned class')

// Updating
it('allows coordinator to update a class')
it('allows teacher to update an assigned class')
it('returns 403 when read-only tries to update a class')

// Deleting
it('allows coordinator to delete a class')
it('returns 403 when teacher tries to delete a class')
it('soft deletes the class — deleted_at is set, record still exists in db')
```

---

## ClassStudentTest.php — Cases to Cover

```php
it('allows coordinator to enrol students in a class')
it('allows teacher to enrol students in an assigned class')
it('returns 403 when read-only tries to enrol students')
it('does not duplicate a record when enrolling an already-enrolled student')

it('allows coordinator to remove a student from a class')
it('returns 403 when read-only tries to remove a student')
```

---

## ClassUserTest.php — Cases to Cover

```php
it('allows coordinator to assign staff to a class')
it('returns 403 when read-only tries to assign staff')
it('does not duplicate a record when assigning an already-assigned user')

it('allows coordinator to remove staff from a class')
it('returns 403 when read-only tries to remove staff')
```

---

## NoteTest.php — Cases to Cover

```php
it('allows teacher to create a note for a student in their class')
it('returns 403 when read-only tries to create a note')
it('returns 422 when note_text is missing')

it('creates one note per student when multiple student_ids are provided')
it('creates one note when a single student_id is provided')

it('returns notes for a student')
it('filters notes by class_id when provided')
```

---

## TenantIsolationTest.php — Cases to Cover

```php
it('does not return classes from another tenant')
it('does not return students from another tenant')
it('does not return notes from another tenant')
it('sets tenant_id correctly when creating a class')
```

---

## Unit — NoteServiceTest.php

```php
it('creates one StudentNote record per student id')
it('sets the correct user_id on each created note')
it('sets the correct class_id on each created note')
```

---

## Unit — ClassDetailResourceTest.php

```php
it('counts students correctly by nccd_level in nccd_summary')
it('returns zero for nccd levels with no students')
```

---

## Running Tests

```bash
cd backend
php artisan test                                    # run all tests
php artisan test --filter ClassTest                 # run one file
php artisan test --filter "coordinator can delete"  # run one case
```
