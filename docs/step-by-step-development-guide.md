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
6. Connect SQLTools in VS Code to the Docker MySQL instance and confirm you can see the database
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
15. ✅ Enable Spatie teams feature in `config/permission.php` — set `teams => true`, `team_foreign_key => tenant_id`; created follow-up migration to add `tenant_id` to `roles`, `model_has_roles`, `model_has_permissions`
16. ✅ Create `InitialiseTenantFromUser` middleware in `app/Http/Middleware/`
17. ✅ Register the middleware alias `tenant` in `bootstrap/app.php`
18. ✅ Define the `tenant` middleware alias in `bootstrap/app.php` (combined with step 17)

---

## Phase 4 — Database Migrations

**Goal:** All tables created in the correct order with proper foreign keys.

19. Create migration: `tenants` table (Stancl publishes this — verify it exists)
20. Create migration: `domains` table (Stancl publishes this — verify it exists)
21. Create migration: `users` table — add `tenant_id` column, ensure `email` has a global unique index (not scoped)
22. Create migration: `year_levels` table with `tenant_id`
23. Create migration: `classes` table with `tenant_id` and `deleted_at`
24. Create migration: `class_users` pivot table — `class_id`, `user_id`, no `tenant_id` (isolation inherited through class)
25. Create migration: `class_students` pivot table — `class_id`, `student_id`, no `tenant_id` (isolation inherited through class)
26. Create migration: `students` table with `tenant_id`, NCCD enum columns, and `deleted_at`
27. Create migration: `student_notes` table with `tenant_id` and `deleted_at`
28. Run `php artisan migrate` — verify all tables appear in SQLTools
29. Publish and run Spatie permission migrations

---

## Phase 5 — Eloquent Models, Enums, Factories, and Test Setup

**Goal:** All models defined with relationships, traits, and casts. Factories and Pest test helpers ready so tests can be written from Phase 7 onwards.

30. Create `app/Enums/NccdLevelEnum.php`
31. Create `app/Enums/NccdCategoryEnum.php`
32. Create/update `User` model — add `BelongsToTenant`, `HasRoles`, `SoftDeletes`, relationships
33. Create `YearLevel` model — add `BelongsToTenant`, relationships
34. Create `SchoolClass` model — add `BelongsToTenant`, `SoftDeletes`, relationships, scopes
35. Create `ClassUser` model — pivot only, no traits
36. Create `ClassStudent` model — pivot only, no traits
37. Create `Student` model — add `BelongsToTenant`, `SoftDeletes`, relationships, casts, `full_name` accessor
38. Create `StudentNote` model — add `BelongsToTenant`, `SoftDeletes`, relationships, casts
39. Create `Tenant` model — add `domains()` relationship
40. Set up `tests/TestCase.php` — base class with tenant initialisation/teardown and `RefreshDatabase` (see `docs/testing.md`)
41. Configure `tests/Pest.php` — add `actingAsRole()` helper function
42. Create `TenantFactory` — generates a tenant record; required by the TestCase base class
43. Create `UserFactory` — generates realistic staff users
44. Create `SchoolClassFactory` — generates class records
45. Create `StudentFactory` — generates students with random NCCD data
46. Create `StudentNoteFactory` — generates note records
47. Create `YearLevelFactory` — generates year level records

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

55. Create `AuthController` with `login`, `logout`, and `user` methods
56. Register auth routes in `routes/api.php` (public — no middleware)
57. Manually verify `POST /api/login` with a seeded user's credentials returns a token
58. Manually verify `GET /api/user` with the token returns the correct user and tenant
59. Manually verify `POST /api/logout` revokes the token
60. Write `tests/Feature/AuthTest.php` covering all cases in `docs/testing.md`
61. Run `php artisan test --filter AuthTest` — all tests pass

---

## Phase 8 — Class Feature (Backend)

**Goal:** All class API endpoints implemented, manually verified, and covered by Pest tests.

62. Create `ClassPolicy` in `app/Policies/`
63. Register `ClassPolicy` in `AuthServiceProvider`
64. Create `ClassRepository` in `app/Repositories/`
65. Create `ClassService` in `app/Services/`
66. Create `StoreClassRequest` and `UpdateClassRequest` in `app/Http/Requests/`
67. Create `ClassListResource` and `ClassDetailResource` in `app/Resources/`
68. Create `ClassStudentResource` and `UserResource` in `app/Resources/`
68a. Create `YearLevelResource` in `app/Resources/`
69. Create `ClassController` in `app/Http/Controllers/`
70. Register class routes in `routes/api.php` under the `tenant` middleware group
71. Create `ClassObserver` and register it in `AppServiceProvider`
71a. Create `YearLevelController` with a single `index` method — returns all year levels for the tenant as a plain resource collection
71b. Register `GET /api/year_levels` route in `routes/api.php` under the `tenant` middleware group
72. Manually verify: GET /classes, GET /year_levels, POST /classes, GET /classes/{id}, PUT /classes/{id}, DELETE /classes/{id}
73. Manually verify: PUT /classes/{id} with updated user_ids — confirm sync removes unsubmitted users and adds new ones
74. Manually verify: DELETE /classes/{id}/students/{studentId}
75. Write `tests/Feature/ClassTest.php` covering all cases in `docs/testing.md`
76. Write `tests/Feature/ClassStudentTest.php` and `tests/Feature/ClassUserTest.php`
77. Write `tests/Unit/ClassDetailResourceTest.php` (NCCD summary calculation)
78. Run `php artisan test --filter ClassTest` — all tests pass

---

## Phase 9 — Student Notes (Backend)

**Goal:** Note creation endpoint working including bulk, manually verified, and covered by Pest tests.

79. Create `StudentNotePolicy` in `app/Policies/`
80. Create `NoteRepository` in `app/Repositories/`
81. Create `NoteService` in `app/Services/` — implement bulk note creation loop
82. Create `StoreNoteRequest` in `app/Http/Requests/`
83. Create `StudentNoteResource` in `app/Resources/`
84. Create `NoteController` in `app/Http/Controllers/`
85. Register note routes in `routes/api.php`
86. Manually verify `GET /api/students/{id}/notes`
87. Manually verify `POST /api/notes` with a single student ID
88. Manually verify `POST /api/notes` with multiple student IDs — confirm one record per student in SQLTools
89. Write `tests/Feature/NoteTest.php` covering all cases in `docs/testing.md`
90. Write `tests/Feature/TenantIsolationTest.php`
91. Write `tests/Unit/NoteServiceTest.php` (bulk creation logic)
92. Run `php artisan test` — all tests pass

---

## Phase 10 — Vue SPA Setup

**Goal:** Vue app running, connected to the Laravel API, with routing and auth store in place.

93. Create `src/types/index.ts` — define all shared TypeScript interfaces based on API response shapes:

```ts
// Auth
export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string[]
  tenant: { id: string; name: string }
}

export interface LoginResponse {
  token: string
  user: Pick<AuthUser, 'id' | 'name' | 'email' | 'roles'>
}

// Shared primitives
export interface YearLevel {
  id: number
  description: string
  sort_order?: number
}

export interface UserSummary {
  id: number
  name: string
  roles: string[]
}

// Pagination
export interface PaginatedMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginatedMeta
}

// Classes — list
export interface ClassSummary {
  total_students: number
  teachers_assigned: number
}

export interface ClassListMeta extends PaginatedMeta {
  summary: ClassSummary
}

export interface ClassListItem {
  id: number
  name: string
  year_level: YearLevel | null
  created_by: { id: number; name: string }
  assigned_users: UserSummary[]
  student_count: number
}

// Classes — detail
export interface NccdSummary {
  QDTP: number
  Supplementary: number
  Substantial: number
  Extensive: number
}

export interface StudentDetail {
  id: number
  full_name: string
  given_name: string
  family_name: string
  year_level: YearLevel | null
  nccd_level: string | null
  nccd_category: string | null
  primary_disability: string | null
  primary_disability_level_formalised: boolean
}

export interface ClassDetail {
  id: number
  name: string
  year_level: YearLevel | null
  created_by: { id: number; name: string }
  assigned_users: UserSummary[]
  nccd_summary: NccdSummary
  students: StudentDetail[]
}

// Students — picker list
export interface StudentListItem {
  id: number
  full_name: string
  given_name: string
  family_name: string
  year_level: YearLevel | null
}

// Notes
export interface StudentNote {
  id: number
  note_text: string
  note_date: string
  note_type: string | null
  confidentiality_level: string | null
  author: { id: number; name: string }
  class: { id: number; name: string }
  created_at: string
}

// API request payloads
export interface StoreClassPayload {
  name: string
  year_level_id: number | null
  user_ids: number[]
  student_ids: number[]
}

export interface StoreNotePayload {
  student_ids: number[]
  class_id: number
  note_text: string
  note_date: string
  note_type: string | null
  confidentiality_level: string | null
}
```

94. Install frontend dependencies: `npm install vue-router@4 pinia axios`
95. Install and configure Tailwind CSS in the frontend
96. Set up shadcn-vue — copy initial base components (Button, Dialog, Input, etc.)
97. Create `src/lib/axios.ts` — configure base URL (`http://localhost:8000`) and Bearer token interceptor
98. Create `src/router/index.ts` — define routes for `/login`, `/classes`, `/classes/:id`
99. Add router guard — redirect unauthenticated users to `/login`
100. Create `src/stores/useAuthStore.ts` — handles login, logout, token persistence in localStorage
101. Create `LoginPage.vue` — email/password form that calls the login endpoint and stores the token

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

## Phase 15 — Documentation

**Goal:** GitHub repo is presentable to Beliven.

125. Write `README.md` at the repo root — project overview, setup instructions, tech stack, architectural decision rationale
126. Review and finalise all files in `docs/`
127. Commit all documentation
