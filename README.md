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

| Layer | Choice |
|---|---|
| Backend | Laravel 13 JSON API |
| Frontend | Vue 3 SPA (TypeScript, Composition API) |
| Auth | Laravel Sanctum — Bearer tokens |
| Multi-tenancy | stancl/tenancy — single-database mode |
| RBAC | spatie/laravel-permission with teams |
| Database | MySQL 8 |
| Styling | Tailwind CSS + shadcn-vue |
| State | Pinia |
| Testing | Pest PHP |

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

These aren't patterns — they're rules for class design. They explain *why* the patterns above are structured the way they are.

| Principle | How it appears in this project |
|---|---|
| **S** — Single Responsibility | Controller handles HTTP. Service handles business logic. Repository handles data access. Form Requests handle validation. Each class has one job. |
| **O** — Open/Closed | The Observer pattern means new reactions to events can be added as new listeners without modifying the service that fires them. |
| **L** — Liskov Substitution | Any class implementing the same repository interface can replace another without the service knowing or breaking. |
| **I** — Interface Segregation | Repositories implement focused interfaces rather than one large interface with methods most implementations wouldn't use. |
| **D** — Dependency Inversion | Services receive repositories via constructor injection. The container wires the dependency — services don't instantiate their own dependencies. |

---

## Multi-Tenancy

Each school is a tenant. Rather than giving each school its own database, every tenant-scoped table has a `tenant_id` column and a global Eloquent scope is applied automatically via the `BelongsToTenant` trait. The developer never manually adds `WHERE tenant_id = ?` to queries.

The tenant is resolved from the authenticated user's `tenant_id` after login, not from subdomains. This was a deliberate decision — subdomain routing would have complicated deployment significantly and added infrastructure overhead that wasn't necessary at this scale.

Role assignments are scoped per tenant using Spatie's teams feature, with `team_foreign_key = tenant_id`. A teacher at School A and a teacher at School B are entirely independent role assignments.

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

## AI-Assisted Development

I used Claude Code throughout this project. This section covers how I structured that workflow, and is an honest account of what I caught, what I pushed back on, and what I took away from the experience.

### The workflow

**Step 1 — Documentation first.**
Before any code was written, I built a full specification package covering every aspect of the application: data models, API contracts, role-based access rules, tenancy approach, frontend design, architecture patterns, naming conventions, and testing strategy. Every design decision was made explicitly in writing before development started. This meant that when something came up during implementation, there was a document to resolve it rather than guessing.

The documentation lives in the `docs/` folder:

| File | Contents |
|---|---|
| `project-overview.md` | Scope, goals, what the application does and doesn't do |
| `models.md` | Every model, fields, relationships, and traits |
| `api-contracts.md` | Every endpoint — request shape, validation, response shape |
| `architecture.md` | Full controller → service → repository flow with method signatures |
| `rbac.md` | Roles, permissions matrix, policy rules |
| `tenancy.md` | How single-database tenancy is implemented |
| `frontend-design.md` | Component tree, loading states, action feedback, role-based UI |
| `design-constraints.md` | Naming conventions, layer rules, what belongs where |
| `testing.md` | Test cases per endpoint, factory setup, Pest conventions |
| `step-by-step-development-guide.md` | Sequenced build roadmap with completion flags |

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
- *"Before continuing with this code change can you explain what the code you are changing does in plain english"* — before a migration patching the Spatie permission tables
- *"Before you make that change explain why it wasnt registered and what the problem this would have caused is"* — before a provider registration fix
- *"Can you break down each point one at a time and await a decision"* — when working through the 14 documentation inconsistencies

**Catching what the AI didn't flag.** When `php artisan tenancy:install` published the `TenancyServiceProvider`, it contained multi-database tenancy code that would have crashed on Laravel 11 and attempted to create per-tenant databases we don't use. I noticed it because the file was open in my IDE and the error was visible. That led to finding two further issues — the provider wasn't registered with the application at all, and a route file was referencing middleware we'd already removed. The AI published all three files without flagging any of them as problems. Reviewing published files rather than just accepting them was the right instinct.

**Asking conceptual questions throughout.** Rather than accepting output, I regularly asked things like *"where is the auth:sanctum middleware?"*, *"do we have seeded data in the tenants table?"*, and *"what is the summary object referring to?"*. These were how I built a mental model of what was actually being built — not how someone rubber-stamps AI output.

---

### What I took from it

The AI is good at generating code that looks correct. It's less reliable at knowing whether that code fits your specific context — your deployment constraints, your scope decisions, your existing architectural choices. The more precisely I defined the context through documentation and phase summaries, the better the output became.

The most valuable moments weren't when I asked the AI to build something. They were when I asked it to explain something I didn't fully understand, then made my own decision based on that explanation.
