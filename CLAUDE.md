# CLAUDE.md — Session Context

This file is read automatically at the start of every Claude Code session. It replaces the need for CONTEXT.md on the Desktop.

---

## What This Project Is

A Laravel 11 SaaS skeleton implementing the "Class" feature from Inspire — a multi-tenant school management platform used by 50+ Australian schools. Portfolio project for Alessandro Pangrazio (SRA Information Technology, Australia), built for a Beliven job application.

Full background: see `docs/project-overview.md`

---

## Repo Structure

```
Class-Functionality/        git root
├── backend/                Laravel 11 JSON API (not yet scaffolded)
├── frontend/               Vue 3 SPA (not yet scaffolded)
├── docker-compose.yml      MySQL 8 local dev container
├── docs/                   Full documentation package
└── CLAUDE.md               This file
```

---

## All Architecture Decisions — Final

| Decision | Choice |
|---|---|
| Backend | Laravel 11 JSON API |
| Frontend | Standalone Vue 3 SPA — NOT Inertia |
| Database (local) | MySQL 8 via Docker |
| Database (production) | MySQL on Railway |
| Multi-tenancy | stancl/tenancy, single-database mode |
| Tenant identification | From authenticated user's `tenant_id` — NOT subdomain |
| Email uniqueness | Globally unique across all tenants |
| Authentication | Laravel Sanctum Bearer tokens |
| RBAC | spatie/laravel-permission + Laravel Policies |
| Soft deletes | SoftDeletes trait (`deleted_at`) |
| API responses | Dedicated API Resources per shape |
| Patterns | Service Layer, Repository, Observer, Builder |
| Components | shadcn-vue (owned, not a package dep) |
| Styling | Tailwind CSS |
| DB GUI (local) | SQLTools VS Code extension + MySQL/MariaDB driver |

---

## Scope — What the Application Does and Does NOT Do

**The UI manages:**
- Classes (CRUD + soft delete)
- Staff assignment to classes (from seeded users)
- Student enrolment in classes (from seeded students)
- Student notes including bulk creation

**Seeder-only (no UI, no API endpoints):**
- Creating tenants (schools)
- Creating users (staff)
- Creating students
- Assigning roles
- Creating year levels

There is no admin screen, no user management screen, no student creation screen.

---

## Key Patterns

- **Controllers** — thin. Call one service method, return one API Resource.
- **Services** — own all business logic and orchestration.
- **Repositories** — own all Eloquent queries. No auth logic.
- **Policies** — own all authorization. Never in repos or models.
- **API Resources** — one class per response shape. No raw arrays returned from controllers.
- **Form Requests** — all validation and permission-level auth checks.

---

## Tenancy

Single-database. Every tenant-scoped table has `tenant_id`. `BelongsToTenant` trait on all tenant models applies the global scope automatically. Tenant is resolved after login via `InitialiseTenantFromUser` middleware which reads `Auth::user()->tenant_id`.

Details: `docs/tenancy.md`

---

## RBAC

Roles: `school-admin`, `coordinator`, `teacher`, `teachers-assistant`, `read-only`

Spatie teams feature is enabled with `team_foreign_key = tenant_id` so roles are scoped per tenant in the single database.

Details: `docs/rbac.md`

---

## Models

All tenant-scoped models use `BelongsToTenant`. Primary models: `User`, `SchoolClass` (not `Class` — PHP reserved word), `Student`, `StudentNote`, `ClassUser`, `ClassStudent`, `YearLevel`.

Details: `docs/models.md`

---

## API Endpoints Summary

- `POST /api/login`, `POST /api/logout`, `GET /api/user`
- `GET|POST /api/classes`, `GET|PUT|DELETE /api/classes/{class}`
- `POST|DELETE /api/classes/{class}/users/{user}`
- `POST|DELETE /api/classes/{class}/students/{student}`
- `GET /api/students`
- `GET /api/users`
- `GET /api/students/{student}/notes`, `POST /api/notes`

Full contracts: `docs/api-contracts.md`

---

## Development Progress

See `docs/step-by-step-development-guide.md` for the full 18-phase roadmap.

Current status: documentation complete, no code scaffolded yet. Next step is Phase 1 — project scaffolding.
