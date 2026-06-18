# Dev Log — Class Functionality

A record of each development phase: what was built, what decisions were made, where the AI was redirected, and any notable architectural moments.

---

## Pre-Development — Documentation Phase

**Completed:** Full documentation package produced before a single line of code was written.

**Documents produced:**
- `docs/project-overview.md`
- `docs/models.md`
- `docs/api-contracts.md`
- `docs/architecture.md`
- `docs/rbac.md`
- `docs/tenancy.md`
- `docs/step-by-step-development-guide.md`
- `docs/design-constraints.md`
- `docs/testing.md`
- `docs/frontend-design.md`
- `CLAUDE.md`

**Key decisions made during documentation:**

- **Single-database tenancy over isolated databases** — AI initially drafted a two-database model. User corrected to single-database with `tenant_id` scoping. Driven by Railway deployment constraints (no wildcard DNS for subdomain routing).
- **Tenant resolved from user, not subdomain** — simplifies Railway deployment; globally unique emails make login lookup trivial.
- **Seeder-only for tenants, users, students** — AI drafted API endpoints and an admin screen for these. User clarified seed data only, no management UI.
- **PDF/report functionality removed** — AI included a Builder pattern and DomPDF. User confirmed no PDF feature exists in scope; removed from all docs.
- **Vue SPA over Inertia** — confirmed early; Laravel is a pure JSON API.
- **TypeScript throughout frontend** — added after initial documentation; Vite template corrected from `vue` to `vue-ts`.
- **Teachers-assistants cannot create or edit classes** — initially given same permissions as teacher. User clarified view + notes only.
- **`sync()` for staff/student assignment** — AI initially used additive `attach` semantics. User clarified the Inspire pattern: multi-select sends full desired state, backend syncs the difference.
- **`ClassFormDialog` is 2-column** — AI documented a simple form. User described the actual design: left column (class details + selected student badges), right column (searchable student picker with +/✓ toggle).
- **Pivot tables (`class_users`, `class_students`) have no `tenant_id`** — isolation is inherited through `SchoolClass`. Avoids `sync()` bypassing model events.
- **`POST /api/classes` returns a message, not `ClassDetailResource`** — user clarified: on create success, frontend re-fetches the class list. No need to return class data.
- **Dashboard summary stats appended to `GET /api/classes` meta** — debated standalone endpoint vs appended. Chose appended for simplicity at this scale.
- **Debounced search, immediate dropdown filters** — no search button; filter trigger behaviour defined after user asked the question.
- **`GET /api/year_levels` added** — gap identified during review; year level filter dropdown had no data source.
- **`GET /api/users` permission changed to `view classes`** — originally required `edit class`, which blocked teachers-assistants from loading the teacher filter on the dashboard.
- **Role-based UI visibility documented** — edit/delete buttons conditionally rendered based on role. User confirmed backend still enforces 403 if bypassed.

---

## Phase 1 — Project Scaffolding

**Completed:** Both applications scaffolded and running locally with a working database connection.

**What was built:**
- Laravel 13 backend scaffolded via `laravel new backend --no-interaction`
- `docker-compose.yml` created at repo root with MySQL 8 container (`class_functionality_mysql`)
- `backend/.env` configured: `APP_NAME=ClassHub`, MySQL connection on port 3307
- `php artisan migrate` ran successfully — default Laravel tables created in `classhub` database
- Vue 3 TypeScript frontend scaffolded via `npm create vite@latest frontend -- --template vue-ts`
- Both `backend/` and `frontend/` confirmed under repo root

**Decisions driven by prompts:**
- **App name changed to ClassHub** — initial `.env` had "Class Functionality". User rejected this and specified "ClassHub". `APP_NAME`, `MYSQL_DATABASE`, `DB_DATABASE` all updated accordingly.
- **Docker port changed to 3307** — local mysqld process already occupied port 3306. User chose to keep both running side-by-side. `docker-compose.yml` changed to `"3307:3306"` and `.env` `DB_PORT` set to 3307.
- **Laravel 13 installed** — `laravel new` installs the current stable release. Not a blocker; CLAUDE.md updated throughout.

**AI drafted, user redirected:**
- AI initially left the default SQLite `.env` in place. User's intent was always MySQL via Docker — `.env` reconfigured after Docker was confirmed running.

**Notable:**
- Step 6 (SQLTools VS Code connection) completed — connected using driver MySQL/MariaDB, host `127.0.0.1`, port `3307`, database `classhub`, user `laravel`, password saved as plaintext (acceptable for local dev). Default Laravel tables confirmed visible in VS Code.
- **TypeScript types file identified as a documentation gap** — no `src/types/` directory or interface definitions existed in any frontend doc. Added `src/types/index.ts` as step 93 of the dev guide (first step of Phase 10) with all shared interfaces derived from the API contracts: `AuthUser`, `ClassListItem`, `ClassDetail`, `StudentDetail`, `StudentNote`, `PaginatedResponse<T>`, and request payload types. `design-constraints.md` updated with the folder entry and a rule enforcing its use. Gap spotted by user before Phase 2 began.

---

