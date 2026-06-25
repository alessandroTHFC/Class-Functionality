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

## Phase 7 — Authentication

**Completed.**

**What was built:**
- `app/Http/Requests/LoginRequest.php` — validates `email` (required, email) and `password` (required, string)
- `app/Http/Resources/AuthUserResource.php` — returns `id`, `name`, `email`, `roles` (from Spatie `getRoleNames()`), and `tenant` (via `whenLoaded`)
- `app/Services/AuthService.php` — `login()` uses `Auth::attempt()` then `createToken()`; throws `ValidationException` with status 401 on bad credentials; `logout()` calls `currentAccessToken()->delete()`
- `app/Http/Controllers/AuthController.php` — thin; injects `AuthService` via constructor; three methods: `login`, `logout`, `user`
- `routes/api.php` — `POST /login` is public; `POST /logout` behind `auth:sanctum`; `GET /user` behind `auth:sanctum` + `tenant`
- `tests/Feature/AuthTest.php` — 6 tests covering: valid login, wrong password (401), missing fields (422), token revocation, authenticated user response, unauthenticated access (401)

**Notable — five errors resolved:**
- **`protected $tenant` inaccessible in Pest closures** — Pest test closures run outside the class scope, so `protected` properties on `TestCase` can't be accessed via `test()->tenant`. Fixed by changing `protected Tenant $tenant` to `public Tenant $tenant` in `TestCase.php`.
- **`createToken()` undefined on User** — the `HasApiTokens` Sanctum trait was missing from `User`. Sanctum's `createToken()` method lives on this trait. Added `HasApiTokens` alongside the other traits.
- **Roles not found in tests** — `RolesAndPermissionsSeeder` is not run automatically in tests; `RefreshDatabase` wipes the DB clean each test. Added `app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id)` and `$this->seed(RolesAndPermissionsSeeder::class)` to `TestCase::setUp()` so roles exist and are scoped correctly for every test.
- **`User` missing `tenant()` relationship** — `GET /api/user` eager-loads the tenant via `$user->load('tenant')` in the controller. The relationship didn't exist on the model. Added `public function tenant(): BelongsTo` returning `$this->belongsTo(Tenant::class)`.
- **Logout second-request returned 200 after token revocation** — The `AuthManager` caches guard instances (and the resolved `$user`) for the lifetime of the application instance. In tests, the same app is reused across all HTTP calls within a test, so the Sanctum guard still held the authenticated user from the logout request. Switched from making a second HTTP request to using `assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId])` which bypasses the guard cache entirely and directly verifies the record was deleted.

---

## Phase 8 — Class Feature (Backend)

**Completed.**

**What was built:**
- `app/Policies/ClassPolicy.php` — 5 policy methods (`viewAny`, `view`, `create`, `update`, `delete`), each delegating to the matching Spatie permission string
- `app/Http/Resources/YearLevelResource.php`, `UserResource.php` — small shared resources reused across list and detail responses
- `app/Http/Resources/ClassStudentResource.php` — full student shape for the class detail view including NCCD fields; uses `->value` to unwrap enum casts to plain strings
- `app/Http/Resources/ClassListResource.php` — list item shape; exposes `students_count` set by `withCount()` in the repository
- `app/Http/Resources/ClassDetailResource.php` — full class shape; computes NCCD summary by filtering the already-loaded students collection in memory — no extra query
- `app/Http/Resources/ClassListCollection.php` — custom `ResourceCollection` that injects tenant-wide `summary` into paginated `meta` via `paginationInformation()`
- `app/Http/Requests/StoreClassRequest.php` and `UpdateClassRequest.php` — validation + permission-level `authorize()` checks
- `app/Repositories/ClassRepository.php` — all Eloquent queries; `list()`, `summary()`, `findWithRelations()`, `create()`, `syncUsers()`, `syncStudents()`, `update()`, `delete()`
- `app/Services/ClassService.php` — orchestration; `list()` returns both paginator and summary; `create()` and `update()` call sync methods after persisting the class
- `app/Observers/ClassObserver.php` — `creating()` sets `created_by_user_id ??= Auth::id()`
- `app/Http/Controllers/YearLevelController.php` — single `index` method, returns year levels ordered by `sort_order`
- `app/Http/Controllers/ClassController.php` — 5 methods (`index`, `store`, `show`, `update`, `destroy`); thin; all work delegated to `ClassService`
- `app/Providers/AppServiceProvider.php` — registers `ClassPolicy` via `Gate::policy()` and wires `ClassObserver` via `SchoolClass::observe()`
- `routes/api.php` — `Route::apiResource('classes', ...)` registers all 5 REST routes; `GET /year_levels` added; both under `auth:sanctum` + `tenant`
- `tests/Feature/ClassTest.php` — 22 tests across all endpoints and all roles
- `tests/Feature/ClassStudentTest.php` — 4 tests covering student sync add, remove, clear, and 403
- `tests/Feature/ClassUserTest.php` — 4 tests covering staff sync add, remove, clear, and 403
- `tests/Unit/ClassDetailResourceTest.php` — 3 unit tests for NCCD summary calculation in isolation

**48 tests, 110 assertions — all passing after Phase 8 (2 tenant isolation tests added inline at Phase 9 start).**

**Notable — four errors resolved:**
- **`DELETE /api/classes/{class}/students/{student}` removed** — originally designed as a quick per-student remove button on the class detail view. User clarified that student add/remove is edit-only functionality, only visible to roles that can open the edit modal. Endpoint, repository method, service method, API contract, testing doc, and CLAUDE.md all updated.
- **`authorize()` undefined on `ClassController`** — Laravel 11 stripped `AuthorizesRequests` from the base `Controller` class. Added `use AuthorizesRequests` to `app/Http/Controllers/Controller.php` so all controllers have access to `$this->authorize()`.
- **`NOT NULL` constraint on `created_by_user_id` in tests** — `ClassObserver::creating()` was setting `$class->created_by_user_id = Auth::id()`, unconditionally overwriting the value that `SchoolClassFactory` had already provided. `Auth::id()` is null in tests using direct factory creation (no HTTP request), causing the insert to fail. Fixed with `??=`: the observer only sets the value if it isn't already present.
- **Policy auto-discovery mismatch** — Laravel auto-discovers policies by matching `ModelName` → `ModelNamePolicy`. Our model is `SchoolClass` but the policy is `ClassPolicy`, not `SchoolClassPolicy`. Auto-discovery fails silently — policies just don't apply. Fixed by explicitly registering `Gate::policy(SchoolClass::class, ClassPolicy::class)` in `AppServiceProvider::boot()`.

**User decisions:**
- Student add/remove scoped to update flow only — no dedicated delete endpoint for individual students
- Comments required on all files going forward (controllers, services, repositories, tests, routes, observers, resources, form requests)

---

## Phase 10 — Vue SPA Setup

**Completed.**

**What was built:**
- `src/types/index.ts` — all TypeScript interfaces for every API response shape and request payload
- `src/lib/axios.ts` — Axios instance pointing at `http://backend.test/api` with Bearer token interceptor reading from localStorage
- `src/lib/utils.ts` — `cn()` helper (clsx + tailwind-merge) used by all shadcn-vue components
- `src/router/index.ts` — routes for `/login`, `/classes`, `/classes/:id`; navigation guard redirects unauthenticated users to `/login` and authenticated users away from `/login`
- `src/stores/useAuthStore.ts` — Pinia store; `login()` hits `POST /api/login` then immediately fetches full user via `GET /api/user`; token and user persisted to localStorage for page refresh survival
- `src/pages/LoginPage.vue` — two-panel layout (dark sidebar left, form right); ClassHub colour palette applied; 401/422 error handling; redirects to `/classes` on success
- `src/components/ui/Button.vue`, `Input.vue`, `Label.vue` — shadcn-vue components with ClassHub design tokens (teal primary, 8px border radius)
- `src/App.vue` — replaced Vite scaffold with `<RouterView />`
- `src/main.ts` — Pinia registered before Router (required because nav guard calls `useAuthStore()`)
- `tailwind.config.js` — ClassHub design tokens added (teal, sidebar, app-bg, text-primary, etc.); Inter font; border radius overrides
- `postcss.config.js`, `style.css` — Tailwind directives and CSS custom properties for shadcn-vue

**Login working end-to-end:** browser → Herd (`backend.test`) → Laravel API → token stored → redirected to `/classes`.

**Notable issues resolved:**
- Node.js 18 incompatible with Vite 7 — upgraded to Node 20 via nvm
- Tailwind v4 installed by default — downgraded to v3 (`tailwindcss@3`) for shadcn-vue compatibility
- `@apply bg-app-bg` in `style.css` caused PostCSS error — custom JIT classes cannot be used with `@apply` in CSS files; removed all `@apply` calls with custom colour names
- Custom Tailwind colour tokens (`bg-teal`, `bg-sidebar`, etc.) work in Vue templates via JIT scanning but NOT via `@apply`
- `InitialiseTenantFromUser` middleware was missing `setPermissionsTeamId()` — found via Postman testing; all authenticated users were getting 403 on note endpoints; fixed and tests still passing

---

## Phase 9 — Student Notes (Backend)

**Completed.**

**What was built:**
- `app/Policies/StudentNotePolicy.php` — `viewAny` (all roles with `view student notes`) and `create` (roles with `add student note`; teachers-assistant is included, read-only is not)
- `app/Http/Resources/StudentNoteResource.php` — note shape with `whenLoaded()` guards on `author` and `schoolClass`; JSON key for the class relation is `class` even though the PHP method is `schoolClass()` (reserved word workaround)
- `app/Http/Requests/StoreNoteRequest.php` — `authorize()` checks `add student note`; validates `student_ids` (required array min:1), `class_id`, `note_text`, `note_date`, `confidentiality_level`
- `app/Repositories/NoteRepository.php` — `forStudent()` with optional `class_id` `when()` filter and eager-loads; `create()` stamps `user_id` from `Auth::id()`
- `app/Services/NoteService.php` — `forStudent()` delegates; `createBulk()` loops over `student_ids` calling `repository->create()` once per student, returns count
- `app/Http/Controllers/NoteController.php` — `index()` uses route model binding for `{student}` (BelongsToTenant gives free cross-tenant 404); `store()` delegates to `NoteService::createBulk()` and returns count in message
- `app/Providers/AppServiceProvider.php` — `Gate::policy(StudentNote::class, StudentNotePolicy::class)` added
- `routes/api.php` — `GET /students/{student}/notes` and `POST /notes` added; comment explains why the bulk endpoint is flat rather than nested
- `tests/Feature/NoteTest.php` — 10 tests: list, class_id filter, response shape, RBAC (read-only allowed to view, forbidden to create), bulk create count, author stamping
- `tests/Unit/NoteServiceTest.php` — 4 unit tests with Mockery mocks verifying delegation and loop count without hitting the database
- Tenant isolation tests (2) added to `tests/Feature/ClassTest.php` as the Phase 9 starting point

**62 tests, 141 assertions — all passing.**

**Bug found during manual Postman testing (not caught by tests):**
- `GET /api/students/{student}/notes` returned 403 for all authenticated users despite correct permissions in the seeder. Root cause: `InitialiseTenantFromUser` middleware was calling `tenancy()->initialize($tenant)` but not `app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id)`. Without this, Spatie's teams-scoped permission check has no team context and `$user->can()` always returns false. In tests this was masked because `TestCase::setUp()` calls `setPermissionsTeamId()` directly before each test. Fixed by adding the call to the middleware immediately after `tenancy()->initialize()`.

**Design notes:**
- Bulk creation is a simple loop in the service — one `StudentNote` row per `student_id` with identical content. No junction table. This mirrors how Inspire works: each student gets their own note record for independent future management.
- `GET /students/{student}/notes` uses nested routing (sub-resource URL) rather than `GET /notes/{studentId}` because `{student}` is resolved via route model binding, giving automatic cross-tenant 404 protection through BelongsToTenant's global scope without any controller code.
- `POST /notes` is a flat route rather than nested under a student because the bulk create payload targets multiple students — nesting under one student ID would misrepresent the request's intent.

---

## Phase 10 — Vue SPA Setup (In Progress)

**Status:** All files written. npm install not yet confirmed complete — run step 94 first on resume.

**What was built this session:**
- `src/types/index.ts` — all TypeScript interfaces matching every API response shape and request payload
- `src/lib/utils.ts` — `cn()` helper (clsx + tailwind-merge) used by all shadcn-vue components
- `src/lib/axios.ts` — Axios instance pointed at `http://backend.test/api`; request interceptor attaches `Authorization: Bearer {token}` from localStorage on every request
- `src/router/index.ts` — Vue Router with `/login`, `/classes`, `/classes/:id`; navigation guard redirects unauthenticated users to `/login` and authenticated users away from `/login`
- `src/stores/useAuthStore.ts` — Pinia store; `login()` posts credentials, then fetches full `/api/user` (with tenant); `logout()` hits the API and clears localStorage; `isAuthenticated` computed from token presence
- `src/pages/LoginPage.vue` — login form with 401/422 error handling; redirects to `/classes` on success
- `src/pages/ClassDashboard.vue` and `ClassDetailPage.vue` — placeholder pages for Phases 11 and 12
- `src/components/ui/Button.vue`, `Input.vue`, `Label.vue` — owned shadcn-vue components
- `src/App.vue` — replaced scaffold with `<RouterView />`
- `src/main.ts` — registers Pinia (before router, required for nav guard) then Vue Router
- `tailwind.config.js` — Tailwind with CSS custom property colour tokens
- `postcss.config.js` — PostCSS with Tailwind and autoprefixer
- `src/style.css` — replaced scaffold CSS with Tailwind directives and shadcn-vue CSS variable definitions
- `vite.config.ts` — added `@` → `src/` path alias
- `tsconfig.app.json` — added matching `paths` entry for TypeScript

**Bug found and fixed during manual Postman testing (Phase 9):**
- `InitialiseTenantFromUser` middleware was not calling `setPermissionsTeamId()` — all `$user->can()` checks returned false in real HTTP requests. Fixed by adding the call after `tenancy()->initialize()`. Tests were masking this because TestCase::setUp() calls it directly.

**Next session: resume Phase 10 from step 94**
1. Run `npm install vue-router@4 pinia axios class-variance-authority clsx tailwind-merge radix-vue lucide-vue-next` in `frontend/`
2. Run `npm install -D tailwindcss postcss autoprefixer` in `frontend/`
3. Run `npm run dev` and confirm the login page renders at the Vite dev server URL
4. Test login end-to-end: sign in → redirect to `/classes` placeholder

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

## Phase 11 — Class Dashboard (Frontend)

**Status: Nearly complete — one backend endpoint outstanding.**

**What was built:**

- `src/stores/useReferenceStore.ts` — Pinia setup store; fetches year levels (`GET /api/year_levels`) and staff users (`GET /api/users`) in parallel on first `load()` call; `loaded` flag prevents repeat fetches across the session; used by both the dashboard filter bar and the class form dialog
- `src/composables/useClasses.ts` — composable owning all class API interactions; exposes `classList` (local ref), `meta`, `loading`, `error`, filter refs (`search`, `yearLevelId`, `userId`, `page`), and CRUD methods (`fetchClasses`, `createClass`, `updateClass`, `deleteClass`); CRUD methods deliberately do NOT auto-refresh — `ClassDashboard` calls `fetchClasses()` explicitly in `onSaved()` and `handleDelete()` so only one re-fetch occurs per mutation
- `src/components/AppSidebar.vue` — 88px icon-only shared layout wrapper used by all authenticated pages; logo mark at top; `BookOpen` nav icon with `border-l-2 border-teal bg-white/10` active state; user initials avatar at bottom (`bg-teal-light text-teal`, derived from `authStore.user?.name`); `LogOut` icon; exposes `<slot />` for page content; wraps the entire page via a flex row
- `src/components/ui/Sonner.vue` — thin wrapper around vue-sonner's `<Toaster>` component (bottom-right, `richColors: true`); mounted once in `App.vue`
- `src/pages/ClassDashboard.vue` — full dashboard page wrapped in `<AppSidebar>`; stat cards (Total Classes, Total Students, Teachers Assigned pulled from `meta.summary`); filter bar (search debounced 300ms via `watch`, year level select, staff select, "Clear filters" ghost button); class table with RouterLink name, year level, comma-joined staff, student count, edit/delete icon buttons; server-side pagination (Previous/Next with "Page X of Y · N classes"); delete confirmation via vue-sonner
- `src/components/ClassFormDialog.vue` — 2-column modal (`max-w-3xl`, Teleported to `<body>`, fade Transition); left column: class name input, staff checkbox list, year level select, selected student badge chips (teal-light pills with × remove button); right column: student picker with name search and year level filter, paginated table (10 rows per page, Name/Year Level/Enrol columns), Plus icon (add) / Check icon (already enrolled), "Showing X–Y of Z students" pagination footer

**Key decisions made this phase:**

- **`classList` is a local ref, not a Pinia store** — user identified that a Pinia class store could serve stale data if another user adds a class between navigations. Local ref in `ClassDashboard` is always re-fetched on mount and after every mutation.
- **`useClasses()` does not auto-refresh** — `ClassFormDialog` imports `useClasses()` to get `createClass`/`updateClass` methods only; it creates a separate composable instance with its own (unused) `classList`. If the composable auto-refreshed, it would trigger a wasted API call against the dialog's isolated list. The caller (dashboard) is always responsible for calling `fetchClasses()`.
- **`wasEditing` captured before `closeDialog()`** — `editTarget.value` is cleared by `closeDialog()`. The success toast message ("Class updated" vs "Class created") must be determined before close is called, so `const wasEditing = !!editTarget.value` is captured first.
- **vue-sonner used for delete confirmation** — a custom `ConfirmToast.vue` component was initially drafted and rejected by the user who noted shadcn-vue already includes Sonner. `toast()` with `duration: Infinity`, `action: { label: 'Yes, delete', onClick }`, and `cancel: { label: 'No' }` replaces the entire custom approach.
- **AppSidebar is 88px icon-only** — initially implemented at 256px with labels. After user reviewed the original design image, they requested reverting to the icon-only layout. Width changed from `w-64` to `w-[88px]`, all label text removed.
- **Student picker is a paginated table, not a scrollable flat list** — user specified the right column should show all students immediately (alphabetical order), filterable client-side by name and year level, with pagination (10 per page) rather than an infinite scroll. Students are loaded on dialog open via a `watch(() => props.open, ...)` watcher and filtered fully client-side — no extra API calls when searching.
- **Plus/Check icons, not checkboxes** — design spec requires a Plus icon button (teal, clickable) when a student is not enrolled, and a static Check icon (grey) when they are. Selected students appear as removable badge chips in the left column.
- **Edit mode loads enrolled student IDs from `GET /api/classes/{id}`** — `ClassListItem` only carries `student_count`; the full enrolled student ID list is needed to pre-select the picker. `ClassFormDialog` calls the class detail endpoint on open in edit mode.

**Outstanding — required to close Phase 11:**

1. **`GET /api/students` backend endpoint (404)** — `ClassFormDialog` calls this endpoint on dialog open to load the student list. It currently returns 404 because the route, controller, policy, repository, and resource do not exist.
   - `app/Http/Resources/StudentListResource.php` — shape: `{ id, full_name, given_name, family_name, year_level }`
   - `app/Policies/StudentPolicy.php` — `viewAny()` checks `view students` (permission already seeded; all roles have it)
   - `app/Repositories/StudentRepository.php` — `list(array $filters)`: eager-loads `yearLevel`, ordered `family_name` then `given_name`, filtered by `search` (LIKE on both name fields) and `year_level_id`, paginated with `per_page` defaulting to 100
   - `app/Http/Controllers/StudentController.php` — `index()`: authorize, call repository, return `StudentListResource::collection()`
   - `routes/api.php` — `Route::get('/students', [StudentController::class, 'index'])` inside `auth:sanctum` + `tenant` group

2. **Alphabetical sort in `ClassFormDialog.vue`** — add `.sort((a, b) => a.full_name.localeCompare(b.full_name))` to the `filteredStudents` computed. The backend returns students ordered by `family_name` then `given_name`, but client-side sort ensures order is preserved after filtering changes the set.

---

## Phase 11 — UI Polish & Fixes

**Status: Complete.**

A dedicated polish pass on Phase 11 output before moving to Phase 12. Items worked through one at a time with user approval between each.

**What was changed:**

- **Filter bar layout (Item 1)** — converted from a flex row to a 12-column CSS grid (`col-span-5/3/3/1`) giving proportional widths to each filter element. Year Level and Staff filter dropdowns converted from native `<select>` to shadcn `Select` components. Null filter state uses `"all"` sentinel with computed get/set wrappers (Radix Vue Select requires non-empty strings).

- **Shadcn components audit (Item 1 extension)** — user identified that the dashboard was using raw divs instead of shadcn components. Full audit performed; all relevant elements migrated: stat cards → shadcn Card/CardHeader/CardContent/CardTitle; filter dropdowns → shadcn Select; class table → shadcn Table/TableHeader/TableBody/TableRow/TableHead/TableCell; edit/delete buttons → Button `variant="ghost" size="icon"` (added `size="icon"` to Button.vue).

- **Stat card icon backgrounds (Item 3)** — all three stat card icons use `bg-purple-bg text-purple-text`. Purple was established as the secondary colour / students-concept colour at this point.

- **Summary stats filtered (Item 4)** — `ClassRepository::summary()` was previously running on unfiltered data. Updated to accept `array $filters` and apply the same search/year_level/user_id conditions as the `list()` query. `ClassService` updated to pass the active filters through to `summary()`.

- **Sidebar avatar — initials contrast (Item 5)** — avatar changed from `bg-teal-light text-teal` to `bg-teal text-white` (matches the logo mark). Root cause of missing initials investigated: `useAuthStore` was storing the Laravel JsonResource wrapper `{ data: { ... } }` as the user object instead of unwrapping it. Fixed by changing `user.value = res.data` to `user.value = res.data.data`. Users must log out and back in to clear stale localStorage.

- **Sidebar avatar popover (Item 5 extension)** — shadcn Popover added to the avatar showing full name, tenant name, and formatted role badges. Popover uses uncontrolled mode (no `open` prop bound). Two bugs resolved: (1) `as-child` on `PopoverTrigger` does not work through Vue slot abstraction — removed in favour of a direct class-prop approach; (2) passing `:open="undefined"` explicitly to `PopoverRoot` triggers Radix Vue's controlled mode — fixed by removing all props from `Popover.vue` and leaving `PopoverRoot` bare.

- **`GET /api/users` endpoint created (Item 2)** — `UserController` and `UserResource` created; route added to `api.php`; `useReferenceStore` already called this endpoint. Staff dropdowns now populate correctly.

- **Dialog width and height (Item 6)** — dialog widened to `w-[90vw] max-w-6xl`, height capped at `max-h-[88vh]`.

- **Dialog column layout (Item 6 extension)** — two-column layout changed from flex to a 12-column CSS grid with left column `col-span-5` and right column `col-span-7`.

- **Assign Staff + Year Level in same row (Item 7)** — left column reorganised to: Class Name (full width) → Assign Staff + Year Level (2-col grid side by side) → Enrolled Students (full width). Staff assignment converted from a checkbox list to a proper shadcn multi-select dropdown.

- **shadcn Select multi-select support** — all five Select components updated to support a `multiple` prop via Vue `provide/inject`. When `multiple` is true: `Select` renders `PopoverRoot` instead of `SelectRoot`; `SelectTrigger` renders `PopoverTrigger`; `SelectContent` renders `PopoverPortal` + `PopoverContent` + `ListboxRoot` (Radix Vue) + `ListboxContent`; `SelectItem` renders `ListboxItem` + `ListboxItemIndicator`. Single-select mode is unchanged. Model value is `string | string[]`; the form uses a computed wrapper to convert `number[]` ↔ `string[]` at the Select boundary.

**Key decisions made this phase:**

- **Purple as secondary colour** — user chose to use `bg-purple-bg text-purple-text` for stat card icons (all three, not just student-related). Purple is now documented as the secondary colour representing the students concept throughout the app.
- **Shadcn components over raw HTML** — user confirmed: all UI elements should use shadcn components where one exists. Raw `<div>`, `<table>`, `<select>` are not acceptable if a shadcn equivalent is available.
- **Popover uncontrolled mode** — do not bind `:open` on `PopoverRoot` unless explicitly managing open state from the parent. Binding `:open="undefined"` still enters controlled mode and breaks toggle behaviour.
- **Multi-select uses ListboxRoot, not SelectRoot** — Radix Vue `SelectRoot` (v1.x) is single-select only. Multi-select is implemented via `ListboxRoot` (which has a `multiple` prop) wrapped inside a `PopoverRoot` for dropdown behaviour. This matches how shadcn-vue implements `<Select multiple>` internally.
- **Staff IDs stay as `number[]` in form state** — `form.userIds` remains `number[]`; conversion to/from `string[]` is handled at the Select boundary via a computed wrapper, not inside the form or payload.

- **Dialog right column — table, filters, and pagination (Item 8)** — removed the outer `border border-brand-border rounded-sm` container around the student table. Converted raw `<table>/<thead>/<tbody>/<tr>/<th>/<td>` to shadcn Table/TableHeader/TableBody/TableRow/TableHead/TableCell components (which have more generous `px-4 py-3` padding vs the previous `px-3 py-2.5`). Converted the native `<select>` year level filter to a shadcn Select. Filter row changed from `flex gap-2` to `grid grid-cols-5 gap-2` with Input on `col-span-3` and the Select wrapper on `col-span-2`. Pagination converted from raw `<button>` elements to shadcn Pagination/PaginationPrevious/PaginationNext components (wrapping Radix Vue `PaginationRoot`/`PaginationPrev`/`PaginationNext`). Also fixed a layout bug where the pagination was being pushed out of view: the right column was missing `overflow-hidden`, so `flex-1` on the inner table container had no bounded height to grow into. Added `overflow-hidden` to the right column div to properly constrain the flex layout. New components created: `Pagination.vue`, `PaginationPrevious.vue`, `PaginationNext.vue`.

- **Dialog header colour (Item 9)** — header background changed to `bg-teal` with `text-white` title and `text-white/70 hover:text-white hover:bg-white/10` close button. Added `rounded-t-sm` to clip the header against the card's border-radius (fixes a white gap visible at the top corners).

- **shadcn Badge component + enrolled student badges** — created `Badge.vue` in `src/components/ui/` using CVA with variants: `default` (teal-light), `secondary` (app-bg + brand-border), `outline`, `purple`, `success`, `warning`, `danger`. Enrolled student badges in ClassFormDialog left column replaced from raw `<span>` to `<Badge variant="purple">` — purple is the secondary/students-concept colour. New component created: `Badge.vue`.

---

## Pre-Phase 12 Polish

**Status: Complete — Groups A, B, and C done.**

### Group A — Sidebar (AppSidebar.vue)

- **shadcn Tooltip components** — created `TooltipProvider.vue`, `Tooltip.vue`, `TooltipTrigger.vue`, `TooltipContent.vue` wrapping Radix Vue primitives. `TooltipProvider` wraps the entire aside so all icons share one tooltip context. Native `title` attributes removed throughout. New components: `TooltipProvider.vue`, `Tooltip.vue`, `TooltipTrigger.vue`, `TooltipContent.vue`.

- **Tooltip label renamed** — "Classes" → "Class Dashboard" on the BookOpen nav icon.

- **Placeholder nav icons** — added three non-functional nav icons below Class Dashboard: Students (`Users`), Reports (`BarChart2`), Settings (`Settings`). All use `text-white/25 cursor-default` to visually distinguish them from active nav items. No route, no click handler — placeholders for future pages.

- **Logout tooltip** — logout button wrapped in `Tooltip` (label: "Sign out"). Native `title` attribute removed.

### Group B — Skeleton Loaders

- **shadcn `Skeleton.vue`** — new component in `src/components/ui/`. Wraps a single `animate-pulse rounded-sm bg-gray-200` div. Accepts a `class` prop for size overrides via `cn()`.

- **ClassDashboard skeletons** — while `loading` is true: stat card row replaced by 3 skeleton `Card` components (title line + icon box + number line); class table replaced by a matching 5-column `Table` with 5 skeleton rows (name, year level, staff, students, action buttons). Once data resolves, real content renders.

- **ClassFormDialog skeletons** — two distinct states:
  - `loadingStudents` (dialog open, any mode): student picker right column shows Table with header + 6 skeleton rows matching the 3-column structure (Name, Year Level, Enrol).
  - `loadingDetail` (edit mode only): full two-column skeleton replacing the entire form body — left column has label + input skeletons for Class Name, Assign Staff, Year Level, and pill-shaped badge skeletons for Enrolled Students; right column has label, filter row, and 6 row skeletons.

- **Dev-only axios delay** — 800ms response interceptor added to `src/lib/axios.ts` behind `import.meta.env.DEV` guard. Makes skeleton loaders visible during local development. Zero impact on production builds. Remove before Railway deployment.

### Group C — Form Validation + Toast (ClassFormDialog.vue)

- **Frontend `validate()` function** — runs before every API call. Checks: class name not blank, at least 1 staff member, at least 1 student. If any fail: populates `errors` ref with field-level messages, fires `toast.warning("Please fill in all mandatory fields before saving.")`, returns `false` to abort the API call.

- **Inline field errors** — the three mandatory fields already had `<p v-if="errors.X">` slots in the template (`errors.name`, `errors.user_ids`, `errors.student_ids`). Frontend validation reuses the same slots — no new template markup needed.

- **`toast` import** — added `import { toast } from "vue-sonner"` to ClassFormDialog. The Sonner instance was already mounted in `App.vue`.

- **Error reset** — `errors.value = {}` runs at the top of `handleSave()` on every attempt, clearing both frontend and API error messages before re-validating.

**⚠️ Technical Debt Note — Form Validation Approach**

The current validation implementation (manual `validate()` function, `errors` ref, watchers per field, toast) is functional but verbose for what it does. Alessandro raised a concern about the amount of boilerplate involved.

shadcn-vue ships `Form`, `FormField`, `FormItem`, `FormControl`, and `FormMessage` components built on **vee-validate + zod** that handle all of this automatically — schema declaration, per-field error state, clearing on change, inline messages — with no custom code. Adopting this would replace the entire validation block with a zod schema and remove the manual watches entirely.

**Decision:** Keep the current implementation for `ClassFormDialog` as it already works. However, any new form introduced in Phase 12 or later (notes composer, bulk note modal, etc.) should use vee-validate + zod from the start rather than repeating the manual pattern.

**Required packages when the time comes:** `vee-validate`, `zod`, `@vee-validate/zod`
**Required shadcn components:** `Form.vue`, `FormField.vue`, `FormItem.vue`, `FormControl.vue`, `FormLabel.vue`, `FormMessage.vue`

### Group C — Fixes and refinements

- **Red ring on invalid inputs** — `Input.vue` and `SelectTrigger.vue` both received an `error` boolean prop. When `true`, the border switches to `border-danger-text` and the focus ring to `focus:ring-danger-text`. ClassFormDialog passes `:error="!!errors.name"` to the Class Name input and `:error="!!errors.user_ids"` to the Assign Staff trigger. Enrolled students area uses only the red text message beneath (no ring — not a standard input element).

- **Real-time error clearing** — three watchers added to ClassFormDialog: `form.name`, `form.userIds` (deep), `form.studentIds` (deep). Each watch deletes its corresponding `errors` key the moment the user corrects the field, clearing both the red border and the error message instantly without waiting for the next save attempt.

- **Toast position and CSS** — `vue-sonner/style.css` was not imported, causing the Toaster to render as unstyled text in the bottom-left of the screen with no positioning, animation, or colour. Fixed by adding `import 'vue-sonner/style.css'` to `main.ts`. Toaster position changed from `bottom-right` to `top-right` so toasts appear above dialog backdrops. The `zIndex` override in `toastOptions.style` was removed — Sonner's own container z-index (`999999999`) is sufficient.

- **SelectContent viewport overflow** — Year Level dropdown (and all single-mode Select dropdowns) was escaping the viewport when the trigger was near the bottom of the screen. Root cause: `max-h` was on `SelectViewport` but the scroll buttons outside the viewport added extra height, pushing the total past the boundary. Fixed by moving `max-h-[var(--radix-select-content-available-height)]` to `RadixSelectContent` and restructuring as a flex column — scroll buttons use `shrink-0`, viewport uses `flex-1 overflow-y-auto`. Dropdown now self-constrains to available space on any screen.

---

## Phase 12 — Class Detail Page (Frontend)

**Status: Complete.**

**Scope note:** Phases 12 and 13 from the original guide were merged into a single implementation pass. Notes (NotesList, NoteComposer, BulkNoteModal) were built alongside the Class Detail page rather than in a separate phase.

---

### New shadcn-vue components

All owned in `src/components/ui/`:

- `Separator.vue` — thin `<hr>`-style divider (Radix Vue `SeparatorRoot`)
- `Textarea.vue` — styled textarea matching Input visual design
- `Checkbox.vue` — Radix Vue `CheckboxRoot` + `CheckboxIndicator` with `Check` icon
- `Tabs.vue`, `TabsList.vue`, `TabsTrigger.vue`, `TabsContent.vue` — Radix Vue tab primitives with ClassHub tokens
- `ScrollArea.vue` — Radix Vue `ScrollAreaRoot` + `Viewport` + `ScrollBar`; used for the student list
- `Dialog.vue`, `DialogTrigger.vue`, `DialogContent.vue`, `DialogHeader.vue`, `DialogTitle.vue`, `DialogFooter.vue`, `DialogClose.vue` — full modal stack; `DialogHeader` uses `bg-teal text-white` matching `ClassFormDialog`

---

### Backend changes

- **`NoteRepository::forStudent()`** — changed `.latest()` to `.orderBy('note_date', 'asc')`. `.latest()` orders by `created_at`; the display should order by the note date the author recorded.
- **`ClassStudentResource`** — added `date_of_birth` field (needed by StudentProfilePanel header).
- **`ClassDetailResource`** — added `updated_at` field (used in Class Info stat card "Last Updated").
- **`SchoolClass::students()` relationship** — added `->orderBy('family_name')->orderBy('given_name')` so enrolled students always load alphabetically regardless of insertion order. Applied at the relationship level so every eager load is automatically sorted.

---

### New feature components

- **`src/composables/useClassDetail.ts`** — composable with `fetchClass`, `deleteClass`, `fetchNotes`, `saveNote`; `.then()` chain syntax consistent with `useClasses`
- **`src/components/StudentListPanel.vue`** — left-pane wrapper; `ScrollArea` so the panel scrolls without affecting page layout
- **`src/components/StudentListItem.vue`** — single row; purple Avatar (size lg), full name, NCCD level badge; highlighted row uses `bg-purple-bg` with teal left border; "NCCD Level: " prefix on level text
- **`src/components/StudentProfilePanel.vue`** — right pane; profile header with xl purple Avatar, NCCD badges, and a metadata column (DOB / Year Level / Disability) on the right; Tabs (Notes / Strategies)
- **`src/components/NotesList.vue`** — scrollable notes area; `gap-2` between NoteCard items; scrolls to bottom on notes update
- **`src/components/NoteCard.vue`** — avatar sits outside the bordered bubble; bordered message with `bg-app-bg`; pencil placeholder icon with Tooltip; `button type="button"` with `cursor-default` (no `disabled` — avoids OS red-circle cursor)
- **`src/components/NoteComposer.vue`** — visible only to `canAddNotes` roles; full `border border-brand-border bg-app-bg rounded-sm p-3 mt-3 shrink-0`; Textarea, date input, Save button
- **`src/components/StrategiesView.vue`** — placeholder Card; "Strategy management will be available in a future update."
- **`src/components/BulkNoteModal.vue`** — Dialog-based; student selector (checkboxes, searchable), note form (text, date, confidentiality); submits to `POST /api/notes`
- **`src/pages/ClassDetailPage.vue`** — orchestrator; breadcrumb; title + role-gated action buttons; 3 stat cards (Students, NCCD Students, Class Info); `grid-cols-5` two-pane layout at `h-[700px]`; ClassFormDialog always rendered; BulkNoteModal

---

### Role-based visibility

- **`hasRole(...allowedRoles)` added to `useAuthStore`** — returns `true` if the user holds at least one of the given role slugs
- **`canCreate`, `canEdit`, `canDelete`, `canAddNotes`** — computed properties added to `useAuthStore` and exported. Both `ClassDashboard` and `ClassDetailPage` destructure what they need from the store — no `hasRole()` calls duplicated in pages

---

### UI polish pass

A series of user-directed refinements applied after the initial build:

| Item | Change |
|---|---|
| Note card avatar | Moved outside the bordered bubble; bubble is `flex-1 min-w-0 border bg-app-bg` |
| Note card pencil | `button type="button" cursor-default`; `disabled` removed (caused OS red-circle cursor on hover) |
| NoteComposer border | Changed from `border-t` only to full `border`; added `bg-app-bg` |
| Profile header metadata | DOB / Year Level / Disability moved to a right-aligned column (label + value on one line each) |
| Tabs flex-col fix | `flex-col` removed from `TabsContent` — overrides Radix's `hidden` attribute and keeps inactive panels in layout; moved to an inner `<div>` |
| Edit dialog populate fix | `v-if="showEditDialog"` removed from `ClassFormDialog`; always rendered, open prop controls visibility; `v-if` prevented `watch(() => props.open)` from firing |
| Reference store on ClassDetailPage | `referenceStore.load()` added to `onMounted` — year level / staff lists were empty without it |
| Student list ordering | `SchoolClass::students()` ordered `->orderBy('family_name')->orderBy('given_name')` at the relationship level |
| AppSidebar help icon | `HelpCircle` placeholder added below Settings; `text-white/25 cursor-default` |
| ClassDashboard "New Class" button | Renamed from "Add New Class"; Plus icon added; `v-if="canCreate"` |
| ClassDashboard action buttons | Edit `v-if="canEdit"`, Delete `v-if="canDelete"` |
| Permission properties | `canCreate`, `canEdit`, `canDelete`, `canAddNotes` moved from local computed blocks in each page into `useAuthStore` |

---

### Notable errors and patterns established

- **`getInitials` extracted to `src/lib/utils.ts`** — was duplicated across AppSidebar, StudentListItem, and NoteCard; single export in utils covers all call sites
- **Avatar `xl` size variant** — `w-20 h-20` / `text-xl` added; prop type changed to use `FallbackVariants['size']` only to resolve TypeScript mismatch between the two CVA configs
- **Tabs + Radix `hidden` attribute gotcha** — Radix Vue sets `display: none` via the HTML `hidden` attribute on inactive `TabsContent`. Any display-setting class on the element itself (`flex-col`, `flex`, `block`) overrides `hidden` and keeps inactive content visible. Never put layout classes directly on `TabsContent` — use an inner wrapper div
- **Dialog `v-if` gotcha** — if a dialog mounts with `open: true`, a `watch(() => props.open)` never fires because there is no false → true transition. Always render dialogs unconditionally; control visibility with the `open` prop. This matches the pattern already in `ClassDashboard`

---

## Post-Phase 12 — Fixes & Polish

**Status: Complete.**

Small fixes and visual polish applied after Phase 12 sign-off.

---

### Seeder — per-tenant user names

**Problem:** Both Springfield Primary School and Riverside Secondary College had identical user names (`Admin User`, `Jane Coordinator`, etc.). When switching between tenant accounts during a demo there was no visual signal in the sidebar that a different tenant was active.

**Fix:**

- `UserSeeder::run()` now accepts a second parameter `array $names = []`, merged over a set of defaults. Names remain the same for Springfield (no change to that tenant's setup).
- `TenantSeeder::seedTenant()` signature extended to accept `array $userNames` and pass it through to `UserSeeder`. Riverside Secondary College now receives distinct names:

  | Role | Springfield | Riverside |
  |---|---|---|
  | school-admin | Admin User | Marco Rossi |
  | coordinator | Jane Coordinator | Giulia Coordinator |
  | teacher | John Teacher | Luca Teacher |
  | teachers-assistant | Sarah Assistant | Sofia Assistant |
  | read-only | Read Only User | Read Only User |

The sidebar avatar popover displays the user's name and tenant name on every page — switching tenants is now immediately obvious from the avatar popover without needing to check the URL or any other indicator.

---

### Cross-tenant data leakage via cached Pinia store (critical)

**Discovered by:** thorough manual testing — switching between tenants in the same browser tab session.

**Symptom:** A user logged in as a Riverside Secondary College account was seeing Springfield Primary School staff names in the "Assign Staff" dropdown inside the class form dialog.

**Root cause:** `useReferenceStore` loads year levels and staff users once and sets a `loaded = true` flag to prevent repeat API calls across navigations. This is correct behaviour within a single tenant session. The problem is that a SPA logout is a JavaScript route change, not a browser page reload — the Pinia store lives in memory for the lifetime of the browser tab, not the login session. When a Springfield user logged out and a Riverside user logged in within the same tab, `load()` saw `loaded = true` and returned immediately without fetching, leaving Springfield's staff list in place.

**Why students weren't affected:** The student list in `ClassFormDialog` is fetched fresh every time the dialog opens via a `watch(() => props.open)` watcher — it has no `loaded` cache. Year levels appeared correct because they are identical across tenants (Foundation to Year 12 are seeded the same way for both).

**Impact:** Any data stored in `useReferenceStore` at logout was potentially visible to the next tenant to log in on the same tab. In this case: staff user names and year levels. With the current seeder, year levels are identical so only user names were visibly wrong. In a real deployment with different year level structures per school, both lists would leak.

**Fix:**
- `reset()` method added to `useReferenceStore` — clears `yearLevels`, `users`, and sets `loaded = false`
- `useAuthStore.logout()` calls `useReferenceStore().reset()` in the `finally` block, ensuring the cache is wiped on every logout regardless of whether the API call succeeds

This guarantees the next login always fetches fresh data scoped to the new tenant's context.

---

### Logout resilience after `migrate:fresh`

**Problem:** Running `php artisan migrate:fresh --seed` wipes the `personal_access_tokens` table. Any browser session holding a now-invalid token could not log out — `POST /api/logout` returned 401, `logout()` threw, and the `finally` block (which clears localStorage) never ran.

**Fix:** `useAuthStore.logout()` now wraps the API call in a `try/finally`. The `finally` block unconditionally clears `token`, `user`, `auth_token`, and `auth_user` from both reactive state and localStorage, regardless of whether the API call succeeded or threw. The user is always redirected to the login screen.

**Workaround for already-stuck sessions:** Open DevTools → Application → Local Storage → delete `auth_token` and `auth_user` → refresh.

---

### Login page — purple accent

**Change:** The left branding panel headline "Class management / made simple." now renders with "made simple." in the secondary purple (`text-[#6941C6]`). This is a two-tone hero text treatment common in modern SaaS products and introduces the purple secondary colour on the first screen a user sees, consistent with its use across the authenticated app (stat card icons, student badges, avatar backgrounds).

No other elements on the login page were changed.

---
