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

