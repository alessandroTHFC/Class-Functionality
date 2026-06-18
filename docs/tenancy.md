# Tenancy

## Approach

This project uses **single-database multi-tenancy** via `stancl/tenancy`. All tenants (schools) share one MySQL database. Every tenant-scoped table has a `tenant_id` column. Stancl's `BelongsToTenant` trait adds a global Eloquent scope that automatically appends `WHERE tenant_id = ?` to every query once the tenant context is initialised for a request.

---

## What Is Shared vs Isolated

| Data | Shared / Isolated | Notes |
|---|---|---|
| `tenants` table | Shared | Platform-level, no tenant_id |
| `domains` table | Shared | Platform-level, no tenant_id |
| `users` | Isolated (tenant_id) | School staff, scoped per school |
| `classes` | Isolated (tenant_id) | |
| `students` | Isolated (tenant_id) | |
| `student_notes` | Isolated (tenant_id) | |
| `class_users` | Isolated (via class_id) | No tenant_id — isolation inherited through SchoolClass |
| `class_students` | Isolated (via class_id) | No tenant_id — isolation inherited through SchoolClass |
| `year_levels` | Isolated (tenant_id) | |
| Spatie roles/permissions | Isolated (team = tenant_id) | Scoped via Spatie teams feature |

---

## Tenant Identification

Tenant is resolved from the **authenticated user**, not from a subdomain.

**Why not subdomain?** Subdomain-based routing requires wildcard DNS and custom domain configuration on Railway. For this portfolio project, resolving from the user is simpler and works on any domain.

**Flow:**

```
1. POST /api/login  →  auth:sanctum middleware authenticates user
2. User record contains tenant_id
3. InitialiseTenantFromUser middleware reads Auth::user()->tenant_id
4. Calls tenancy()->initialize($tenant) — Stancl activates global scope
5. All subsequent Eloquent queries on tenant models automatically include WHERE tenant_id = ?
6. Request completes, Stancl tears down tenant context
```

---

## Middleware

### `InitialiseTenantFromUser`

Applied to all authenticated tenant routes. Runs after `auth:sanctum`.

```php
class InitialiseTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        tenancy()->initialize($tenant);

        return $next($request);
    }
}
```

**Middleware group for tenant routes:**
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ThrottleRequests::class.':api',
    SubstituteBindings::class,
],

'tenant' => [
    'auth:sanctum',
    InitialiseTenantFromUser::class,
],
```

Routes that require tenancy:
```php
Route::middleware('tenant')->group(function () {
    Route::apiResource('classes', ClassController::class);
    // ...
});
```

Public routes (login) do not use the `tenant` middleware group.

---

## The `BelongsToTenant` Trait

Every tenant-scoped model uses Stancl's `BelongsToTenant` trait. This trait:
- Adds a global scope to all queries: `->where('tenant_id', tenant()->id)`
- Automatically sets `tenant_id` when creating a new record

```php
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SchoolClass extends Model
{
    use SoftDeletes, BelongsToTenant;
    // ...
}
```

No manual `tenant_id` handling is required in services, repositories, or controllers.

---

## Email Uniqueness

`users.email` has a globally unique index — not scoped per tenant. This is intentional:

- It makes login simple: `User::where('email', $email)->first()` always returns exactly one user
- There is no need for a "which school are you from?" field on the login form
- It means the same person cannot have accounts at two different schools with the same email address (acceptable constraint for this portfolio project)

```php
// Migration
$table->string('email')->unique(); // global unique, not scoped to tenant_id
```

---

## Tenant Onboarding

Tenants are **not created via a UI or API endpoint**. They are created via a database seeder.

`database/seeders/TenantSeeder.php` creates:
1. A `Tenant` record
2. A `Domain` record (optional, for future subdomain support)
3. The tenant context is initialised
4. Roles and permissions are seeded for the tenant
5. Demo users (one per role) are created
6. Demo students with NCCD data are created
7. Demo classes with enrolled students and assigned staff are created

```php
$tenant = Tenant::create(['name' => 'Springfield Primary School']);

tenancy()->initialize($tenant);

// seed roles, users, students, classes...

tenancy()->end();
```

---

## Stancl Configuration (`config/tenancy.php`)

Key settings for single-database mode:

```php
'tenant_model' => \App\Models\Tenant::class,

// Single database — do not use separate DB per tenant
'database' => [
    'based_on_connection' => false,
],

// Models that carry tenant_id
'tenant_aware_models' => [
    \App\Models\User::class,
    \App\Models\SchoolClass::class,
    \App\Models\Student::class,
    \App\Models\StudentNote::class,
    \App\Models\YearLevel::class,
],
```
