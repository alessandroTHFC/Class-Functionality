# ClassHub

A learning project built by Alessandro Pangrazio to explore AI-assisted development, Laravel architecture patterns, and full-stack application design.

I chose to build a class management application because it's functionality I've worked with before on a production application — which meant I already understood the business rules and domain logic. That let me focus entirely on the things I actually wanted to learn: clean architecture, design patterns, and how to work effectively with AI development tools.

---

## What It Does

ClassHub is a multi-tenant web application used by school staff to manage classes, student enrolments, and student notes.

**Staff can:**

- View and search a paginated list of classes across year levels
- Create and edit classes — including assigning teachers and enrolling students in a single save operation
- View per-class student detail including NCCD disability support levels
- Write notes against individual students or bulk-create notes across an entire class

**What it deliberately doesn't do:**
There is no admin screen, no user management UI, no student creation screen. Teachers, students, and schools are seeded data. The application manages what happens to that data, not the data itself. Keeping scope tight was intentional — it meant I could go deep on the parts that matter rather than wide on features that don't.

---

## Tech Stack

| Layer         | Choice                                  |
| ------------- | --------------------------------------- |
| Backend       | Laravel 13 JSON API                     |
| Frontend      | Vue 3 SPA (TypeScript, Composition API) |
| Auth          | Laravel Sanctum — Bearer tokens         |
| Multi-tenancy | stancl/tenancy — single-database mode   |
| RBAC          | spatie/laravel-permission with teams    |
| Database      | MySQL 8                                 |
| Styling       | Tailwind CSS + shadcn-vue               |
| State         | Pinia                                   |
| Testing       | Pest PHP                                |

I chose a standalone Vue SPA over Inertia because using Laravel as a pure JSON API means the API contract has to be properly defined rather than implied by server-side rendering. It also mirrors how I'd build something deployed across separate services.

---

## Architecture and Design Patterns

Every layer of this application exists because a specific pattern or principle demanded it. I wanted to be able to explain not just what each pattern is, but why it's here and what problem it solves.

### The full request flow

```
HTTP Request
    ↓
Middleware          — Decorator pattern (auth, tenant scope)
    ↓
Form Request        — Single Responsibility (validation only)
    ↓
Controller          — thin MVC layer
    ↓
Service             — business logic
    ↓ fires events
Repository          — data access only
    ↓
Eloquent Model      — Active Record
    ↓
API Resource        — DTO (Data Transfer Object)
    ↓
JSON Response
```

---

### Architectural Patterns

**MVC — Model View Controller**
The foundation. Laravel enforces this. Models represent data, Controllers handle HTTP requests, and the Vue SPA handles display. Every Laravel project uses MVC — knowing it is the baseline, not the differentiator.

**Service Layer**
Controllers are deliberately thin in this project. The service layer is where all business logic lives — class creation, student sync, bulk note creation. The controller's only job is to receive the request, call the service, and return the response.

```php
class ClassController {
    public function store(StoreClassRequest $request): JsonResponse {
        $this->classService->create($request->validated());
        return response()->json(['message' => 'Class created successfully.'], 201);
    }
}
```

**Repository Pattern**
The service layer doesn't know or care how data is fetched — it asks the repository. All Eloquent queries live in repository classes. This keeps the service layer testable without hitting the database, and means query logic is in one place rather than scattered across the codebase.

```
Controller → Service → Repository → Eloquent Model
```

Authorization lives in Policies. Business rules live in Services. Queries live in Repositories. Each layer has one job.

---

### Gang of Four (GoF) Patterns

**Builder**
Constructs a complex object step by step without requiring all parameters upfront. Eloquent's query builder is a textbook implementation:

```php
SchoolClass::query()
    ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
    ->when($yearLevelId, fn($q) => $q->where('year_level_id', $yearLevelId))
    ->with(['assignedUsers', 'students'])
    ->paginate(15);
```

Each method configures the query without executing it. `paginate()` is the final build step.

**Observer**
Defines a one-to-many dependency — when one object changes state, its dependents are notified automatically. Laravel's event system implements this. The service fires an event and doesn't need to know who's listening:

```php
// Service fires — doesn't care who reacts
event(new UserAssignedToClass($class, $user));

// Listener reacts independently
class SendAssignmentNotification {
    public function handle(UserAssignedToClass $event): void { ... }
}
```

New reactions to an event — a second email, an audit log entry — can be added by writing a new listener without touching the service. This is the Open/Closed Principle in practice.

**Decorator**
Wraps an object to extend its behaviour without modifying it. Laravel Middleware is the Decorator pattern. Every API request passes through `auth:sanctum` → `tenant` — each middleware wraps the next, adding behaviour without changing what comes before or after it.

**Factory**
Provides an interface for creating objects without specifying the exact class. Laravel Model Factories implement this — used in tests and seeders to create realistic data without hardcoding values.

---

### Application / Enterprise Patterns

**Data Transfer Object (DTO) — implemented as API Resources**
A DTO is an object whose only job is to carry data across a boundary. API Resources play this role — they transform the internal Eloquent model into the external API contract:

```php
class ClassListResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'year_level'     => new YearLevelResource($this->yearLevel),
            'assigned_users' => UserSummaryResource::collection($this->assignedUsers),
            'student_count'  => $this->students_count,
        ];
    }
}
```

The database schema and the API contract are decoupled. Either can change without forcing a change in the other.

**Active Record**
Eloquent implements the Active Record pattern — each model represents a table row and knows how to persist itself (`save()`, `delete()`). This is worth knowing in contrast to the Data Mapper pattern (used in Doctrine), where models are plain objects and a separate class handles persistence. Laravel chose Active Record deliberately for its simplicity and developer experience.

---

### SOLID Principles

These aren't patterns — they're rules for class design. They explain _why_ the patterns above are structured the way they are.

| Principle                     | How it appears in this project                                                                                                                    |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **S** — Single Responsibility | Controller handles HTTP. Service handles business logic. Repository handles data access. Form Requests handle validation. Each class has one job. |
| **O** — Open/Closed           | The Observer pattern means new reactions to events can be added as new listeners without modifying the service that fires them.                   |
| **L** — Liskov Substitution   | Any class implementing the same repository interface can replace another without the service knowing or breaking.                                 |
| **I** — Interface Segregation | Repositories implement focused interfaces rather than one large interface with methods most implementations wouldn't use.                         |
| **D** — Dependency Inversion  | Services receive repositories via constructor injection. The container wires the dependency — services don't instantiate their own dependencies.  |

---

## Multi-Tenancy

Each school is a tenant. Rather than giving each school its own database, every tenant-scoped table has a `tenant_id` column and a global Eloquent scope is applied automatically via the `BelongsToTenant` trait. The developer never manually adds `WHERE tenant_id = ?` to queries.

The tenant is resolved from the authenticated user's `tenant_id` after login, not from subdomains. This was a deliberate decision — subdomain routing would have complicated deployment significantly and added infrastructure overhead that wasn't necessary at this scale.

Role assignments are scoped per tenant using Spatie's teams feature, with `team_foreign_key = tenant_id`. A teacher at School A and a teacher at School B are entirely independent role assignments.

### Known extension point — multi-school organisations

The current model is intentionally flat: one tenant equals one school. This covers the majority of use cases and keeps the architecture simple.

A natural next requirement would be supporting school groups — a diocese or education network that manages multiple schools under one account. This would introduce a `School` model sitting between the tenant and everything else:

```
Tenant (Diocese of Melbourne)
  └── School (St Mary's Primary)
  └── School (St Joseph's College)
        └── Class → Student → Notes
        └── User
```

Every tenant-scoped table would gain a `school_id` column. Users, students, and classes would each belong to a specific school, while the tenant boundary still governs data isolation between organisations. Cross-school reporting (NCCD rollups across the diocese) would be possible because all schools share the same tenant.

This was deliberately not implemented here. The scope is single-school tenants and adding the layer now would mean building for a requirement that doesn't exist in this project. The architecture doesn't prevent it — the `BelongsToTenant` trait and middleware pattern extend cleanly — but the right time to add it is when there's an actual requirement driving it, not speculatively.

---

## Project Structure

```
Class-Functionality/
├── backend/        Laravel 13 JSON API
├── frontend/       Vue 3 TypeScript SPA
├── docs/           Full specification written before development started
├── docker-compose.yml
└── dev-log.md      Record of decisions, redirections, and architectural moments
```

---

## Local Setup

**Requirements:** PHP 8.3 (via Herd), Node.js, Docker Desktop

```bash
# Start the database
docker compose up -d

# Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend
cd frontend
npm install
npm run dev
```

---

## Laravel Concepts Worth Knowing

Things I learned or clarified while building this. Each entry is a Laravel convention or behaviour that wasn't obvious to me upfront — recorded here so I don't have to rediscover them.

---

### Query Scopes — defined with `scope`, called without it

You define a scope with the `scope` prefix, but you call it without it. Laravel strips it automatically and injects the `$query` argument — you only pass your own parameters. This is like creating a reusable LINQ filter, it shortens the syntax and makes it easier to read.

```php
// Definition on the model
public function scopeSearch(Builder $query, string $term): Builder
{
    return $query->where('name', 'like', "%{$term}%");
}

// Usage in a repository — called as ->search(), not ->scopeSearch()
SchoolClass::query()
    ->search('maths')
    ->assignedTo($user)
    ->paginate(15);
```

The reason for the prefix is that it signals to Laravel's internals that this is a scope — so it can inject `$query`, chain correctly, and not conflict with Eloquent's own methods.

---

### `$table` — overriding the guessed table name

Laravel derives the table name from the model class name: StudlyCase → snake_case → plural. `SchoolClass` would resolve to `school_classes`. Our table is `classes`, so we override it explicitly.

```php
class SchoolClass extends Model
{
    protected $table = 'classes';
}
```

Without this, every query would hit `school_classes` and fail silently at runtime.

---

### `$fillable` — mass assignment protection

Any column you want to set via `Model::create([...])` or `$model->fill([...])` must be listed in `$fillable`. Without it, Laravel silently ignores the field — no error, the value just doesn't get saved.

```php
protected $fillable = ['tenant_id', 'name', 'year_level_id', 'created_by_user_id'];
```

This is a security boundary. If a user sends an unexpected field through an API request and it isn't in `$fillable`, it can't end up written to the database regardless of what the controller does.

---

### `$casts` — automatic type conversion

Tells Laravel to convert a raw database value to a PHP type on read, and back on write. You never manually convert — it just works.

```php
protected $casts = [
    'date_of_birth'                       => 'date',
    'primary_disability_level_formalised' => 'boolean',
    'nccd_level'                          => NccdLevelEnum::class,
    'nccd_category'                       => NccdCategoryEnum::class,
];
```

With the enum cast, `$student->nccd_level` returns an `NccdLevelEnum` instance rather than a raw string. Saving works the same way — assign the enum value, Laravel writes the string. This prevents invalid values from being stored and gives IDE autocompletion on enum cases.

---

### Accessors and `$appends` — computed attributes

An accessor is a virtual attribute that doesn't exist as a database column. Laravel recognises the `get{Name}Attribute()` naming convention and makes it accessible as a property.

```php
protected $appends = ['full_name'];

public function getFullNameAttribute(): string
{
    return "{$this->given_name} {$this->family_name}";
}
```

`$student->full_name` works as a regular property. Without `$appends`, the attribute is accessible in PHP but won't appear in JSON responses — adding it to `$appends` tells Laravel to include it every time the model is serialised.

---

### Relationship naming — singular vs plural

Laravel doesn't enforce this, but the convention is consistent across the ecosystem:

- `belongsTo` and `hasOne` → **singular**: `yearLevel()`, `createdBy()`, `student()`
- `hasMany` and `belongsToMany` → **plural**: `classes()`, `students()`, `notes()`

It reads naturally: `$class->students` (many things) vs `$student->yearLevel` (one thing). Deviating from it makes the codebase harder to read.

---

### Custom FK names — the second argument on relationships

When a foreign key doesn't follow Laravel's default convention (`{model}_id`), you pass the column name as the second argument.

```php
// FK column is created_by_user_id, not user_id
public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by_user_id');
}

// Relationship is named 'author' but FK column is user_id
public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

Without the second argument, Laravel would look for a column named `school_class_id` (or whatever it derives) and find nothing.

---

### `RefreshDatabase` — test isolation without manual cleanup

Adding `use RefreshDatabase` to a test class wraps every test in a database transaction that rolls back after the test completes. The database is always clean at the start of each test — no truncation, no leftover data from a previous run.

```php
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);
    }
}
```

The alternative — manually truncating tables or re-seeding — is slow and fragile. `RefreshDatabase` is the standard Laravel testing convention for keeping tests isolated and fast.

---

## AI-Assisted Development

I used Claude Code throughout this project. This section covers how I structured that workflow, and is an honest account of what I caught, what I pushed back on, and what I took away from the experience.

### The workflow

**Step 1 — Documentation first.**
Before any code was written, I built a full specification package covering every aspect of the application: data models, API contracts, role-based access rules, tenancy approach, frontend design, architecture patterns, naming conventions, and testing strategy. Every design decision was made explicitly in writing before development started. This meant that when something came up during implementation, there was a document to resolve it rather than guessing.

The documentation lives in the `docs/` folder:

| File                                | Contents                                                           |
| ----------------------------------- | ------------------------------------------------------------------ |
| `project-overview.md`               | Scope, goals, what the application does and doesn't do             |
| `models.md`                         | Every model, fields, relationships, and traits                     |
| `api-contracts.md`                  | Every endpoint — request shape, validation, response shape         |
| `architecture.md`                   | Full controller → service → repository flow with method signatures |
| `rbac.md`                           | Roles, permissions matrix, policy rules                            |
| `tenancy.md`                        | How single-database tenancy is implemented                         |
| `frontend-design.md`                | Component tree, loading states, action feedback, role-based UI     |
| `design-constraints.md`             | Naming conventions, layer rules, what belongs where                |
| `testing.md`                        | Test cases per endpoint, factory setup, Pest conventions           |
| `step-by-step-development-guide.md` | Sequenced build roadmap with completion flags                      |

**Step 2 — AI-generated development guide.**
Once the documentation was in place, I used the AI to generate a detailed step-by-step development guide broken into phases, with individual numbered tasks within each phase. This created a clear roadmap where progress could be tracked and work was naturally segregated — each phase had a defined goal and a defined set of deliverables before moving to the next.

**Step 3 — Documentation review for inconsistencies.**
Before starting development, I asked the AI to analyse the entire documentation package for ambiguity or inconsistencies. This surfaced 14 issues — gaps in the API contracts, contradictions between role definitions, missing endpoints, undefined behaviours. Rather than working through them all at once, I asked the AI to explain each issue one at a time and waited for my decision before moving to the next. Several of these would have caused real problems during development if they'd been left unresolved. Finding them at the documentation stage is significantly cheaper than finding them in code.

**Step 4 — Dev-log requirement.**
Before development started, I instructed the AI to maintain a `dev-log.md` updated after every phase. Each entry records what was built, what decisions were made, and — importantly — any areas where I pushed back or redirected the AI's approach. That log exists as an audit trail of my judgment calls, not just the AI's output. It's at the repo root and is intended to be read alongside the code.

**Step 5 — Phase alignment before every phase.**
The AI was required to summarise the upcoming phase in plain English before any work began, and could not start until I confirmed we were aligned. This wasn't just a formality — on multiple occasions the summary surfaced an assumption I disagreed with, which was corrected before a single line was written.

---

### Where I pushed back

**On scope during documentation.** Early drafts included PDF report generation, an admin screen for managing users and students, and patterns that went beyond what the application needed. I removed all of it across the documentation before development started.

**On architecture decisions.** The initial tenancy approach used subdomain-based tenant resolution. I redirected it to user-based resolution because subdomain routing would have added deployment complexity that wasn't justified at this scale.

**Before approving code changes.** Several times I rejected a change mid-execution and asked for a plain-English explanation before proceeding:

- _"Before continuing with this code change can you explain what the code you are changing does in plain english"_ — before a migration patching the Spatie permission tables
- _"Before you make that change explain why it wasnt registered and what the problem this would have caused is"_ — before a provider registration fix
- _"Can you break down each point one at a time and await a decision"_ — when working through the 14 documentation inconsistencies

**Catching what the AI didn't flag.** When `php artisan tenancy:install` published the `TenancyServiceProvider`, it contained multi-database tenancy code that would have crashed on Laravel 11 and attempted to create per-tenant databases we don't use. I noticed it because the file was open in my IDE and the error was visible. That led to finding two further issues — the provider wasn't registered with the application at all, and a route file was referencing middleware we'd already removed. The AI published all three files without flagging any of them as problems. Reviewing published files rather than just accepting them was the right instinct.

**Catching unnecessary code before it was written.** During Phase 5, the AI was about to add `tenant_id` to the `UserFactory` definition. I questioned why it wasn't visible in the diff, which prompted the explanation that `BelongsToTenant` sets `tenant_id` automatically from the tenancy context via an Eloquent lifecycle event — so hardcoding it in the factory would have been redundant at best, and misleading at worst. The factory was left as-is. Asking "I can't see the difference in that code" before accepting the change was what caught it.

**Asking conceptual questions throughout.** Rather than accepting output, I regularly asked things like _"where is the auth:sanctum middleware?"_, _"do we have seeded data in the tenants table?"_, and _"what is the summary object referring to?"_. These were how I built a mental model of what was actually being built — not how someone rubber-stamps AI output.

---

### What I took from it

The AI is good at generating code that looks correct. It's less reliable at knowing whether that code fits your specific context — your deployment constraints, your scope decisions, your existing architectural choices. The more precisely I defined the context through documentation and phase summaries, the better the output became.

The most valuable moments weren't when I asked the AI to build something. They were when I asked it to explain something I didn't fully understand, then made my own decision based on that explanation.
