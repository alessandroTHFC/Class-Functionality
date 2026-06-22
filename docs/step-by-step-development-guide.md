# Step-by-Step Development Guide

This is the sequential build roadmap. Each step should be completed and verified before moving to the next. Steps within a phase that are independent can be done in any order, but phases must be completed in sequence.

**Testing rhythm:** For each backend phase, build the feature → verify manually with a REST client → write Pest tests before moving to the next phase. Tests are written alongside the feature, not at the end.

---

## Phase 1 — Project Scaffolding

**Goal:** Get both applications running locally with a working database connection.

1. ✅ Scaffold the Laravel project inside the repo root: `laravel new backend --no-interaction`
2. ✅ Add `docker-compose.yml` at the repo root with a MySQL 8 service
3. ✅ Start Docker: `docker compose up -d`
4. ✅ Configure `backend/.env` — set `DB_*` variables to point at Docker MySQL
5. ✅ Run `php artisan migrate` — confirm the default Laravel tables are created
6. ✅ Connect SQLTools in VS Code to the Docker MySQL instance and confirm you can see the database
7. ✅ Scaffold the Vue project: `npm create vite@latest frontend -- --template vue-ts`
8. ✅ Confirm both `backend/` and `frontend/` exist under the repo root

---

## Phase 2 — Install and Configure Packages

**Goal:** All major packages installed and minimally configured.

9. ✅ Install Sanctum: `composer require laravel/sanctum` → run `php artisan install:api` (Laravel 13 method — also creates `routes/api.php`)
10. ✅ Install Stancl Tenancy: `composer require stancl/tenancy` → run `php artisan tenancy:install` (publishes config + migrations)
11. ✅ Install Spatie Permission: `composer require spatie/laravel-permission` → run `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
    11a. ✅ Install Pest PHP: `composer require pestphp/pest --dev --with-all-dependencies` (not shipped by default in this scaffold)
12. ✅ Configure Sanctum guard in `config/auth.php` — set API guard to use Sanctum
13. ✅ Configure CORS in `config/cors.php` — allow requests from the Vue dev server origin (`http://localhost:5173`)

---

## Phase 3 — Tenancy Configuration

**Goal:** Stancl configured for single-database mode with tenant-from-user resolution.

14. ✅ Configure `config/tenancy.php` — removed `DatabaseTenancyBootstrapper` (single-database mode uses `BelongsToTenant` trait, not per-tenant DB switching)
15. ✅ Enable Spatie teams feature in `config/permission.php` — set `teams => true`, `team_foreign_key => tenant_id`; note: on a fresh `migrate:fresh`, the permission tables migration handles `tenant_id` automatically when teams is enabled in config — no follow-up migration needed
16. ✅ Create `InitialiseTenantFromUser` middleware in `app/Http/Middleware/`
17. ✅ Register the middleware alias `tenant` in `bootstrap/app.php`
18. ✅ Define the `tenant` middleware alias in `bootstrap/app.php` (combined with step 17)

---

## Phase 4 — Database Migrations

**Goal:** All tables created in the correct order with proper foreign keys.

19. ✅ Create migration: `tenants` table (Stancl publishes this — verify it exists)
20. ✅ Create migration: `domains` table (Stancl publishes this — verify it exists)
21. ✅ Create migration: `users` table — add `tenant_id` column, ensure `email` has a global unique index (not scoped)
22. ✅ Create migration: `year_levels` table with `tenant_id`
23. ✅ Create migration: `classes` table with `tenant_id` and `deleted_at`
24. ✅ Create migration: `class_users` pivot table — `class_id`, `user_id`, no `tenant_id` (isolation inherited through class)
25. ✅ Create migration: `class_students` pivot table — `class_id`, `student_id`, no `tenant_id` (isolation inherited through class)
26. ✅ Create migration: `students` table with `tenant_id`, NCCD enum columns, and `deleted_at`
27. ✅ Create migration: `student_notes` table with `tenant_id` and `deleted_at`
28. ✅ Run `php artisan migrate:fresh` — all 13 migrations ran cleanly; all tables verified
29. ✅ Spatie permission migrations — published and run in Phase 2; `tenant_id` column included automatically on fresh migrate when teams is enabled in config

---

## Phase 5 — Eloquent Models, Enums, Factories, and Test Setup

**Goal:** All models defined with relationships, traits, and casts. Factories and Pest test helpers ready so tests can be written from Phase 7 onwards.

30. ✅ Create `app/Enums/NccdLevelEnum.php`
31. ✅ Create `app/Enums/NccdCategoryEnum.php`
32. ✅ Create/update `User` model — add `BelongsToTenant`, `HasRoles`, `SoftDeletes`, relationships
33. ✅ Create `YearLevel` model — add `BelongsToTenant`, relationships
34. ✅ Create `SchoolClass` model — add `BelongsToTenant`, `SoftDeletes`, relationships, scopes
35. ✅ Create `ClassUser` model — pivot only, no traits
36. ✅ Create `ClassStudent` model — pivot only, no traits
37. ✅ Create `Student` model — add `BelongsToTenant`, `SoftDeletes`, relationships, casts, `full_name` accessor
38. ✅ Create `StudentNote` model — add `BelongsToTenant`, `SoftDeletes`, relationships, casts
39. ✅ Create `Tenant` model — add `domains()` relationship; update `config/tenancy.php` to point to `App\Models\Tenant`
40. ✅ Set up `tests/TestCase.php` — base class with tenant initialisation/teardown and `RefreshDatabase`
41. ✅ Configure `tests/Pest.php` — add `actingAsRole()` helper function
42. ✅ Create `TenantFactory` — generates a tenant record; required by the TestCase base class
43. ✅ Create `UserFactory` — existing factory kept; `tenant_id` omitted (set automatically by `BelongsToTenant` from tenancy context)
44. ✅ Create `SchoolClassFactory` — generates class records
45. ✅ Create `StudentFactory` — generates students with random NCCD data
46. ✅ Create `StudentNoteFactory` — generates note records
47. ✅ Create `YearLevelFactory` — generates year level records

---

## Phase 6 — Seeders

**Goal:** A single `php artisan db:seed` creates one fully-populated demo school.

48. Create `RolesAndPermissionsSeeder` — seeds all roles and permissions (see `docs/rbac.md`)
49. Create `TenantSeeder` — creates the demo tenant, initialises tenancy context, calls sub-seeders
50. Create `UserSeeder` — creates one user per role (coordinator, teacher, teachers-assistant, read-only, school-admin) with realistic names and emails
51. Create `YearLevelSeeder` — seeds Foundation through Year 12
52. Create `StudentSeeder` — seeds ~30 students with varied NCCD data across year levels
53. Create `ClassSeeder` — seeds 3–5 classes with students enrolled and staff assigned
54. Register all seeders in `DatabaseSeeder.php`
55. Run `php artisan db:seed` and verify data in SQLTools

---

## Phase 7 — Authentication

**Goal:** Login and logout endpoints working and covered by Pest tests.

55. ✅ Create `LoginRequest` form request — validates `email` (required, email) and `password` (required, string)
56. ✅ Create `AuthUserResource` — shapes the user response with `id`, `name`, `email`, `roles`, and `tenant` (loaded via `whenLoaded`)
57. ✅ Create `AuthService` — owns `login()` (calls `Auth::attempt()`, issues Sanctum token) and `logout()` (deletes `currentAccessToken()`)
58. ✅ Create `AuthController` — thin controller; `login`, `logout`, `user` methods; injects `AuthService`
59. ✅ Add `tenant()` `belongsTo` relationship to `User` model (required for `GET /api/user` to eager-load tenant)
60. ✅ Add `HasApiTokens` Sanctum trait to `User` model (required for `createToken()`)
61. ✅ Register auth routes in `routes/api.php` — `POST /login` public; `POST /logout` behind `auth:sanctum`; `GET /user` behind `auth:sanctum` + `tenant`
62. ✅ Write `tests/Feature/AuthTest.php` — 6 tests, 22 assertions, all passing
63. ✅ Run `php artisan test tests/Feature/AuthTest.php` — all 6 tests pass

---

## Phase 8 — Class Feature (Backend)

**Goal:** All class API endpoints implemented, manually verified, and covered by Pest tests.

62. ✅ Create `ClassPolicy` in `app/Policies/` — 5 methods: `viewAny`, `view`, `create`, `update`, `delete`; each delegates to the matching Spatie permission
63. ✅ Create `YearLevelResource` in `app/Http/Resources/`
64. ✅ Create `UserResource` in `app/Http/Resources/` — returns `id`, `name`, `roles`
65. ✅ Create `ClassStudentResource` in `app/Http/Resources/` — full student shape for the class detail response
66. ✅ Create `ClassListResource` in `app/Http/Resources/` — list item shape with `student_count` (from `withCount`)
67. ✅ Create `ClassDetailResource` in `app/Http/Resources/` — full class shape including NCCD summary computed from loaded students collection
68. ✅ Create `ClassListCollection` in `app/Http/Resources/` — custom `ResourceCollection` that injects the tenant-wide `summary` into the paginated `meta` via `paginationInformation()`
69. ✅ Create `StoreClassRequest` in `app/Http/Requests/`
70. ✅ Create `UpdateClassRequest` in `app/Http/Requests/`
71. ✅ Create `ClassRepository` in `app/Repositories/` — `list()`, `summary()`, `findWithRelations()`, `create()`, `syncUsers()`, `syncStudents()`, `update()`, `delete()`
72. ✅ Create `ClassService` in `app/Services/` — `list()`, `find()`, `create()`, `update()`, `delete()`
73. ✅ Create `ClassObserver` in `app/Observers/` — `creating()` sets `created_by_user_id` from `Auth::id()`
74. ✅ Create `YearLevelController` in `app/Http/Controllers/`
75. ✅ Create `ClassController` in `app/Http/Controllers/` — `index`, `store`, `show`, `update`, `destroy`
    > Note: `DELETE /api/classes/{class}/students/{student}` removed — student add/remove is edit-only functionality, handled exclusively through the PUT update flow
76. ✅ Register `ClassPolicy` and `ClassObserver` in `AppServiceProvider::boot()` — policy mapped manually because `SchoolClass` → `ClassPolicy` doesn't follow auto-discovery naming; observer wired so `creating()` fires on every create call
77. ✅ Register all class and year level routes in `routes/api.php` under `auth:sanctum` + `tenant` middleware — `apiResource('classes', ...)` covers all 5 standard REST routes in one declaration
78. ✅ Write `tests/Feature/ClassTest.php` — 22 tests covering list, create, show, update, delete for all relevant roles
79. ✅ Write `tests/Feature/ClassStudentTest.php` and `tests/Feature/ClassUserTest.php` — 8 tests covering sync add, sync remove, clear all, and 403 for unauthorised roles
80. ✅ Write `tests/Unit/ClassDetailResourceTest.php` — 3 tests covering NCCD summary counts including zero cases and empty class
81. ✅ Run `php artisan test` — 46 tests, 106 assertions, all passing

---

## Phase 9 — Student Notes (Backend)

**Goal:** Note creation endpoint working including bulk, manually verified, and covered by Pest tests.

79. ✅ Create `StudentNotePolicy` in `app/Policies/`
80. ✅ Create `NoteRepository` in `app/Repositories/`
81. ✅ Create `NoteService` in `app/Services/` — implement bulk note creation loop
82. ✅ Create `StoreNoteRequest` in `app/Http/Requests/`
83. ✅ Create `StudentNoteResource` in `app/Resources/`
84. ✅ Create `NoteController` in `app/Http/Controllers/`
85. ✅ Register note routes in `routes/api.php`
86. ✅ Manually verify `GET /api/students/{id}/notes`
87. ✅ Manually verify `POST /api/notes` with a single student ID
88. ✅ Manually verify `POST /api/notes` with multiple student IDs — confirm one record per student in SQLTools
89. ✅ Write `tests/Feature/NoteTest.php` — 10 tests (list, filter by class, shape, RBAC, bulk create, author stamping)
90. ✅ Tenant isolation for classes added directly to `tests/Feature/ClassTest.php` (2 tests)
91. ✅ Write `tests/Unit/NoteServiceTest.php` — 4 tests (delegation, class_id passthrough, bulk loop count, single student)
92. ✅ Run `php artisan test` — 62 tests, 141 assertions, all pass

---

## Phase 10 — Vue SPA Setup

**Goal:** Vue app running, connected to the Laravel API, with routing and auth store in place.

93. Create `src/types/index.ts` — define all shared TypeScript interfaces based on API response shapes:

```ts
// Auth
export interface AuthUser {
  id: number;
  name: string;
  email: string;
  roles: string[];
  tenant: { id: string; name: string };
}

export interface LoginResponse {
  token: string;
  user: Pick<AuthUser, "id" | "name" | "email" | "roles">;
}

// Shared primitives
export interface YearLevel {
  id: number;
  description: string;
  sort_order?: number;
}

export interface UserSummary {
  id: number;
  name: string;
  roles: string[];
}

// Pagination
export interface PaginatedMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginatedMeta;
}

// Classes — list
export interface ClassSummary {
  total_students: number;
  teachers_assigned: number;
}

export interface ClassListMeta extends PaginatedMeta {
  summary: ClassSummary;
}

export interface ClassListItem {
  id: number;
  name: string;
  year_level: YearLevel | null;
  created_by: { id: number; name: string };
  assigned_users: UserSummary[];
  student_count: number;
}

// Classes — detail
export interface NccdSummary {
  QDTP: number;
  Supplementary: number;
  Substantial: number;
  Extensive: number;
}

export interface StudentDetail {
  id: number;
  full_name: string;
  given_name: string;
  family_name: string;
  year_level: YearLevel | null;
  nccd_level: string | null;
  nccd_category: string | null;
  primary_disability: string | null;
  primary_disability_level_formalised: boolean;
}

export interface ClassDetail {
  id: number;
  name: string;
  year_level: YearLevel | null;
  created_by: { id: number; name: string };
  assigned_users: UserSummary[];
  nccd_summary: NccdSummary;
  students: StudentDetail[];
}

// Students — picker list
export interface StudentListItem {
  id: number;
  full_name: string;
  given_name: string;
  family_name: string;
  year_level: YearLevel | null;
}

// Notes
export interface StudentNote {
  id: number;
  note_text: string;
  note_date: string;
  confidentiality_level: string | null;
  author: { id: number; name: string };
  class: { id: number; name: string };
  created_at: string;
}

// API request payloads
export interface StoreClassPayload {
  name: string;
  year_level_id: number | null;
  user_ids: number[];
  student_ids: number[];
}

export interface StoreNotePayload {
  student_ids: number[];
  class_id: number;
  note_text: string;
  note_date: string;
  confidentiality_level: string | null;
}
```

94. Install frontend dependencies: `npm install vue-router@4 pinia axios`
95. Install and configure Tailwind CSS in the frontend
96. Set up shadcn-vue — copy initial base components (Button, Dialog, Input, etc.)
97. Create `src/lib/axios.ts` — configure base URL (`http://localhost:8000`) and Bearer token interceptor
98. Create `src/router/index.ts` — define routes for `/login`, `/classes`, `/classes/:id`
99. Add router guard — redirect unauthenticated users to `/login`
100.  Create `src/stores/useAuthStore.ts` — handles login, logout, token persistence in localStorage
101.  Create `LoginPage.vue` — email/password form that calls the login endpoint and stores the token

---

## Phase 11 — Class Dashboard (Frontend)

**Goal:** Authenticated users can see a paginated, searchable list of classes.

102. Create `src/stores/useClassStore.ts` — fetches class list, handles pagination state
103. Create `src/composables/useClasses.ts` — wraps API calls (list, create, update, delete)
104. Create `ClassDashboard.vue` page — table/card list of classes with search input and pagination
105. Create `ClassFormDialog.vue` — modal for create and edit; includes year level select, staff multi-select, student multi-select (all from seeded data via API)
106. Wire up create class button → dialog → `POST /api/classes`
107. Wire up edit button → dialog pre-populated → `PUT /api/classes/{id}`
108. Wire up delete button → confirmation → `DELETE /api/classes/{id}`

---

## Phase 12 — Class Detail (Frontend)

**Goal:** Clicking a class opens a two-pane view showing enrolled students and per-student data.

109. Create `ClassDetail.vue` page — two-pane layout (student list left, student detail panel right)
110. Create `StudentList.vue` component — lists enrolled students, clicking one selects them
111. Create `StudentPanel.vue` component — shows selected student's NCCD data and a Notes tab
112. Fetch class detail via `GET /api/classes/{id}` and display the NCCD summary counts in the header

---

## Phase 13 — Student Notes (Frontend)

**Goal:** Staff can view existing notes and create new ones, including bulk creation.

113. Create `NotesList.vue` component — displays notes inside the StudentPanel Notes tab
114. Create `BulkNoteModal.vue` — multi-student selector + note form; submits to `POST /api/notes`
115. Wire up "Add Note" button in StudentPanel for single-student note creation
116. Wire up "Bulk Note" button in ClassDetail for bulk note creation across selected students
117. Refresh notes list after submission

---

## Phase 14 — Deployment to Railway

**Goal:** Application running on Railway, accessible via a public URL.

118. Create a Railway project and provision a MySQL service
119. Configure environment variables in Railway for the Laravel backend (`DB_*`, `APP_KEY`, `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`)
120. Deploy the Laravel backend to Railway (connect GitHub repo, set root to `backend/`)
121. Run `php artisan migrate --seed` on Railway via the Railway console
122. Build the Vue SPA: `npm run build` — update the Axios base URL to the Railway backend URL
123. Deploy the Vue frontend to Railway (or Vercel/Netlify — any static host)
124. Smoke test: login, view classes, create a note

---
