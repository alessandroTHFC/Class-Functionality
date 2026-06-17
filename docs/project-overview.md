# Project Overview

## What We Are Building

A Laravel 11 SaaS skeleton implementing the "Class" feature from Inspire — a multi-tenant school management platform serving 50+ Australian schools. This is a portfolio project demonstrating Laravel proficiency, AI-first development workflow, and software engineering fundamentals to Beliven, an Italian software studio.

The project is not a 1:1 replication of the original. It is a deliberate architectural improvement — applying patterns that were missing or incorrectly applied the first time.

---

## The Domain

**Classes** in the Australian school context are teaching groups (e.g. "Year 9 Science"). The Class feature lets school staff:

- Create and manage classes with a name and optional year level
- Enrol students into classes
- Assign staff (teachers, coordinators) to classes
- View per-student NCCD disability data within a class
- Add notes to students — including **bulk note creation**: write one note, select multiple students, save for all
- Soft-delete classes (deactivation, not permanent removal)

**NCCD** (National Consistent Collection of Data) is the Australian government's framework for identifying students with disability. Students have an NCCD level of need (QDTP, Supplementary, Substantial, Extensive), a category (Cognitive, Physical, Sensory, Social/Emotional), and a primary disability. The class detail view shows NCCD summary counts in the header.

---

## What Was Wrong With the Original (Inspire)

| Anti-pattern | Original Behaviour | This Project |
|---|---|---|
| Authorization in repositories | `SetSecurityContext()` + `UserHasRole()` called inside repo methods | Auth in Policies only. Repos query data, nothing else. |
| Dataset params | `?dataset=class-dashboard` switches payload shape on one endpoint | Dedicated API Resources per response shape |
| Multi-tenancy | `clientId` threaded through every method call manually | Automatic scoping via Stancl middleware |
| Soft delete | `ClassRowState` FK to a reference table | Standard `deleted_at` timestamp via SoftDeletes trait |
| Thin service layer | Service mostly delegates straight to repository | Services own all business logic and orchestration |

---

## Tech Stack

| Layer | Technology | Package/Version |
|---|---|---|
| Backend framework | Laravel 11 | — |
| Authentication | Laravel Sanctum | `laravel/sanctum` |
| Multi-tenancy | Tenancy for Laravel | `stancl/tenancy` ^3.x |
| RBAC | Spatie Laravel Permission | `spatie/laravel-permission` ^6.x |
| Frontend | Vue 3 SPA | Vite, Vue Router 4, Pinia |
| HTTP client | Axios | — |
| Styling | Tailwind CSS | ^3.x |
| Component library | shadcn-vue | (owned components, not a package dep) |
| Database (local) | MySQL 8 | Docker |
| Database (production) | MySQL | Railway managed service |

---

## Architectural Decisions

### Multi-Tenancy: Stancl Tenancy for Laravel

Each school is a tenant. Tenants are identified by subdomain (e.g. `springfield-primary.app.test`). Stancl intercepts the request, resolves the tenant from the subdomain, and applies a global `tenant_id` scope to all tenant-aware models automatically.

All tenants share a single MySQL database. Every tenant-scoped table has a `tenant_id` column. Stancl's `BelongsToTenant` trait adds a global Eloquent scope so queries are automatically filtered without manual ID threading. Platform-level tables (tenants, domains) have no `tenant_id` and are not scoped.

### RBAC: Spatie Laravel Permission + Laravel Policies

Roles and permissions are named and stored in the database per tenant. Authorization is enforced in Laravel Policies (`ClassPolicy`, `StudentNotePolicy`). Controllers call `$this->authorize()`. No permission checks exist in repositories or models.

### Service Layer

Controllers are thin. They accept a validated Form Request, call a service method, and return an API Resource. All business logic — conditional checks, orchestration, side effects — lives in the Service layer.

### Repository Pattern

Repositories encapsulate all Eloquent queries. Services call repositories; they never write Eloquent queries directly. Repositories are strictly data access — no auth, no business logic.

### Observers

Model Observers handle side effects triggered by model events. The `ClassObserver` listens for the `created` event and dispatches a notification to assigned staff. This keeps side-effect logic out of services and controllers.

### API Resources

Dedicated Laravel API Resources per response shape replace the `?dataset=` param from Inspire:

- `ClassListResource` — lightweight, for the dashboard list
- `ClassDetailResource` — full class data including enrolled students
- `ClassStudentResource` — nested student data within a class context
- `StudentNoteResource` — note data for the student panel

### Frontend: Standalone Vue SPA

The frontend is a separate Vue 3 SPA that communicates with the Laravel backend via JSON API. It is not served by Laravel (no Inertia.js). The Vue app authenticates using Sanctum Bearer tokens stored in `localStorage`.

---

## Repository Structure

```
Class-Functionality/        (git root)
├── backend/                Laravel 11 JSON API
├── frontend/               Vue 3 SPA
├── docker-compose.yml      Local MySQL 8 container
├── CLAUDE.md               Claude Code session context
└── docs/                   This documentation package
```

---

## Scope

### In Scope
- Class management (CRUD + soft delete)
- Student enrolment and NCCD data display
- Staff assignment to classes
- Note creation including bulk notes
- RBAC with 6 roles
- Multi-tenancy (one school per tenant)
- Demo seeder with realistic data

### Out of Scope
- Initial Adjustments and intervention strategies
- File attachments on notes
- SMS / school management system integration
- Full notification system (log-based stub only)
- Student profile pages beyond class context
- Billing and subscription management
