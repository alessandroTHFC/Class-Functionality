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

## Phase 2 — Install and Configure Packages

**Completed.**

**What was built:**
- `laravel/sanctum` ^4.3 installed; `php artisan install:api` run — created `routes/api.php`, published personal access tokens migration
- `stancl/tenancy` ^3.10 installed; `php artisan tenancy:install` run — published `config/tenancy.php`, `routes/tenant.php`, `TenancyServiceProvider`, and tenancy migrations
- `spatie/laravel-permission` ^8.0 installed; config and migration published
- `pestphp/pest` ^4.7 installed as dev dependency (not in original scaffold)
- `config/auth.php` — `api` guard added with `driver: sanctum`
- `config/cors.php` — published and `allowed_origins` set to `['http://localhost:5173']`
- `php artisan migrate` — all 4 new migrations ran: `tenants`, `domains`, `personal_access_tokens`, `permission_tables`

---

## Phase 3 — Tenancy Configuration

**Completed.**

**What was built:**
- `config/tenancy.php` — `DatabaseTenancyBootstrapper` removed; single-database mode relies on `BelongsToTenant` trait on models, not per-tenant database switching
- `config/permission.php` — `teams` enabled, `team_foreign_key` set to `tenant_id`
- Follow-up migration created and run: added `tenant_id` column to `roles`, `model_has_roles`, and `model_has_permissions` (Spatie teams feature requires this column but migration had already run without it)
- `app/Http/Middleware/InitialiseTenantFromUser.php` — reads `Auth::user()->tenant_id`, finds the tenant, calls `tenancy()->initialize($tenant)`
- `bootstrap/app.php` — middleware registered as alias `tenant`; routes can now use `->middleware(['auth:sanctum', 'tenant'])`

**Notable:**
- Spatie `teams` migration catch: enabling teams after the initial migration requires a manual follow-up migration. Documented in the dev guide step.
- **`TenancyServiceProvider` rewritten** — the file published by `php artisan tenancy:install` is a multi-database template that doesn't suit our setup. Two problems: (1) it referenced `Jobs\CreateDatabase`, `Jobs\MigrateDatabase`, and `Jobs\DeleteDatabase` which create per-tenant databases on every tenant record creation — we don't do this; (2) `makeTenancyMiddlewareHighestPriority()` called `$this->app[\Illuminate\Contracts\Http\Kernel::class]` which crashes on Laravel 11 because the HTTP Kernel no longer exists. The provider was rewritten to only include the core tenancy lifecycle events (`TenancyInitialized` → `BootstrapTenancy`, `TenancyEnded` → `RevertToCentralContext`) which is all single-database tenancy needs. Spotted by user reviewing the open file in the IDE — would not have caused an immediate crash but `makeTenancyMiddlewareHighestPriority()` would have thrown on any request.
- **`TenancyServiceProvider` not registered** — `php artisan tenancy:install` creates the provider file but does not add it to `bootstrap/providers.php` (stancl hasn't updated for Laravel 11's new provider registration approach). Added manually. Without this, tenancy lifecycle event listeners never attached — cache/queue scoping wouldn't work and context wouldn't clean up between requests.
- **`routes/tenant.php` cleaned** — published by tenancy:install with domain-based middleware we don't use. File emptied and replaced with an explanatory comment. Was never loaded (since `mapRoutes()` was removed from the provider) but left in place would have caused confusion.
- Full codebase scan confirmed no other references to removed multi-database patterns or the Laravel 10 HTTP Kernel.

---

**SSL issue resolved during this phase:**
- Composer SSL cert verification was failing (`curl error 60`) on the corporate network because Herd's bundled `cacert.pem` did not include the corporate proxy's CA certificate
- Windows trusted root certs (77 certs) exported to `C:\Temp\windows-certs.pem` using PowerShell
- Herd `php.ini` updated: `curl.cainfo` and `openssl.cafile` now point to `C:\Temp\windows-certs.pem` instead of Herd's default bundle
- All subsequent `composer require` commands worked without special flags
- Root cause: corporate network SSL inspection proxy presents its own cert; PHP/curl doesn't trust it unless the corp CA is in its cert bundle. The Windows cert store already had it (pushed via Group Policy); Herd did not.

**Decisions made:**
- `php artisan install:api` used instead of manual `vendor:publish` — this is the Laravel 13 preferred method and also wires up the `routes/api.php` file which doesn't exist by default
- Pest added during Phase 2 rather than later — caught the gap early since the testing docs depend on it

---

## Phase 4 — Database Migrations

**Completed.**

**What was built:**
- `0001_01_01_000000_create_users_table` modified — added `tenant_id` (string, FK to tenants) and `softDeletes()` to the default Laravel users migration
- `2026_06_19_100000_create_year_levels_table` — `tenant_id`, `description`, `sort_order`, timestamps
- `2026_06_19_100001_create_students_table` — `tenant_id`, name fields, `date_of_birth`, `year_level_id` (nullable FK), NCCD columns, `deleted_at`
- `2026_06_19_100002_create_classes_table` — `tenant_id`, `name`, `year_level_id` (nullable FK), `created_by_user_id` (FK to users), `deleted_at`
- `2026_06_19_100003_create_class_users_table` — pivot: `class_id`, `user_id`, cascade delete on both FKs, no `tenant_id`
- `2026_06_19_100004_create_class_students_table` — pivot: `class_id`, `student_id`, cascade delete on both FKs, no `tenant_id`
- `2026_06_19_100005_create_student_notes_table` — `tenant_id`, `student_id`, `class_id`, `user_id`, `note_text`, `note_date`, `confidentiality_level`, `deleted_at`
- `php artisan migrate:fresh` ran — all 13 migrations completed successfully

**Notable:**
- `add_tenant_id_to_permission_tables` migration deleted — it was a patch created in Phase 3 to add the Spatie teams `tenant_id` column after the initial migration had already run without it. On a fresh database with teams already enabled in config, the permission tables migration generates the column automatically. The patch migration caused a "duplicate column" error on `migrate:fresh` and was no longer needed.
- **`note_type` field removed** — documented in models, API contracts, frontend design, and the dev guide. User identified that there is no UI functionality to select a note type, making the field purposeless at this stage. Removed from the `student_notes` migration and all documentation before running migrate.

---

## Phase 6 — Seeders

**Completed.**

**What was built:**
- `RolesAndPermissionsSeeder` — creates all 7 permissions and 5 roles (`school-admin`, `coordinator`, `teacher`, `teachers-assistant`, `read-only`) with correct permission assignments; uses `firstOrCreate` so it's idempotent
- `UserSeeder` — creates one user per role with predictable emails (e.g. `coordinator@springfield.demo`); password `Classhub1234` via `UserFactory`
- `YearLevelSeeder` — seeds Foundation through Year 12 (13 levels) with `sort_order` 0–12
- `StudentSeeder` — seeds 30 students with random NCCD data distributed across year levels
- `ClassSeeder` — seeds 4 named classes with specific staff assignments and enrolled students; uses `sync()` for pivot tables
- `TenantSeeder` — orchestrates both tenants (Springfield Primary School, Riverside Secondary College) via a private `seedTenant()` helper method; calls `setPermissionsTeamId()` before seeding roles to correctly scope them per tenant
- `DatabaseSeeder` — replaced Laravel's default stub; calls `TenantSeeder` only

**Notable — three errors resolved:**
- **Guard mismatch (`web` vs `sanctum`)** — roles were seeded with `guard_name = 'sanctum'` but Spatie looks up roles using the User model's default guard (`web`). Sanctum handles authentication, but Spatie's permission lookups always use the model's declared guard. Changed `guard_name` to `web` in `RolesAndPermissionsSeeder` and `rbac.md`. An attempt to change the default auth guard to `sanctum` was reverted after discovering Spatie's `Guard::getNames()` only scans explicitly declared guards in `config/auth.guards`, not Sanctum's dynamically registered guard.
- **Spatie `tenant_id` column type mismatch** — Spatie's published migration defines `tenant_id` as `unsignedBigInteger` on `roles`, `model_has_roles`, and `model_has_permissions`. Our tenant IDs are UUIDs (strings). All three occurrences changed to `string` in the published migration.
- **`school_class_id` FK derivation** — Eloquent derives the pivot FK from the model class name: `SchoolClass` → `school_class_id`. The actual column is `class_id`. Fixed by specifying FK columns explicitly on both `belongsToMany` calls in `SchoolClass`: `belongsToMany(User::class, 'class_users', 'class_id', 'user_id')` and `belongsToMany(Student::class, 'class_students', 'class_id', 'student_id')`.
- **Seeder parameter injection** — `$this->call(Seeder::class, false, $params)` injects parameters into `run()` as method arguments (via Laravel's container), not as class property setters. `UserSeeder` and `ClassSeeder` initially used a `public string $emailDomain` property — changed to `run(string $emailDomain = 'demo.com')` so the container resolves it correctly. The bug was hidden on the first tenant (seeded fine with the default) and only surfaced on the second tenant (duplicate email violation).

**User decision:**
- Two tenants requested (not one) — enables tenant isolation testing in later phases. Springfield Primary School (`springfield.demo`) and Riverside Secondary College (`riverside.demo`).

---

## Phase 5 — Eloquent Models, Enums, Factories, and Test Setup

**Completed.**

**What was built:**
- `app/Enums/NccdLevelEnum.php` and `NccdCategoryEnum.php` — PHP backed string enums used in `Student` model casts
- `app/Models/User.php` — updated to add `BelongsToTenant`, `HasRoles` (Spatie), `SoftDeletes`; replaced PHP 13-style attribute syntax (`#[Fillable]`) with traditional `$fillable` and `$hidden` properties; added `assignedClasses()`, `createdClasses()`, `notes()` relationships
- `app/Models/YearLevel.php` — `BelongsToTenant`, `classes()`, `students()` relationships
- `app/Models/SchoolClass.php` — `BelongsToTenant`, `SoftDeletes`, `$table = 'classes'` override, full relationship set, `scopeSearch()` and `scopeAssignedTo()` query scopes
- `app/Models/ClassUser.php` and `ClassStudent.php` — minimal pivot models, no traits
- `app/Models/Student.php` — `BelongsToTenant`, `SoftDeletes`, NCCD enum casts, `full_name` accessor, `$appends`
- `app/Models/StudentNote.php` — `BelongsToTenant`, `SoftDeletes`, `author()` and `schoolClass()` named relationships
- `app/Models/Tenant.php` — extends Stancl's base Tenant model, adds `domains()` relationship
- `config/tenancy.php` — `tenant_model` updated from Stancl's class to `App\Models\Tenant`
- `tests/TestCase.php` — `RefreshDatabase`, tenant creation and initialisation in `setUp()`, `tenancy()->end()` in `tearDown()`
- `tests/Pest.php` — created; `uses(TestCase::class)->in('Feature')`, `actingAsRole()` global helper
- `database/factories/` — `TenantFactory`, `YearLevelFactory`, `SchoolClassFactory`, `StudentFactory`, `StudentNoteFactory` created; `UserFactory` kept as-is

**Notable:**
- **`class()` relationship renamed to `schoolClass()`** on `StudentNote` — `class` is a reserved word in PHP and cannot be used as a method name. Updated `docs/models.md` to reflect this.
- **`config/tenancy.php` update required** — Stancl's config defaults to its own Tenant model. Creating a custom `App\Models\Tenant` required explicitly pointing the config to it, otherwise Stancl would continue resolving tenants from its own model and the factory/relationship wouldn't apply.
- **`tenant_id` omitted from `UserFactory`** — `BelongsToTenant` sets `tenant_id` automatically via Eloquent's `creating` event from the active tenancy context. Tests that need it explicitly pass it as `User::factory()->create(['tenant_id' => test()->tenant->id])`.
- Application boots cleanly after all changes — confirmed via `php artisan about`.

---

