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

48. ✅ Create `RolesAndPermissionsSeeder` — seeds all roles and permissions (see `docs/rbac.md`)
49. ✅ Create `TenantSeeder` — creates the demo tenant, initialises tenancy context, calls sub-seeders
50. ✅ Create `UserSeeder` — creates one user per role (coordinator, teacher, teachers-assistant, read-only, school-admin) with realistic names and emails
51. ✅ Create `YearLevelSeeder` — seeds Foundation through Year 12
52. ✅ Create `StudentSeeder` — seeds ~30 students with varied NCCD data across year levels
53. ✅ Create `ClassSeeder` — seeds 3–5 classes with students enrolled and staff assigned
54. ✅ Register all seeders in `DatabaseSeeder.php`
55. ✅ Run `php artisan db:seed` and verify data in SQLTools

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

93. ✅ Create `src/types/index.ts` — define all shared TypeScript interfaces based on API response shapes:

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

94. ✅ Install frontend dependencies: `npm install vue-router@4 pinia axios class-variance-authority clsx tailwind-merge radix-vue lucide-vue-next` + `npm install -D tailwindcss@3 postcss autoprefixer`
95. ✅ Install and configure Tailwind CSS — `tailwind.config.js`, `postcss.config.js`, `style.css` rewritten with CSS vars and Tailwind directives
96. ✅ Set up shadcn-vue base components — `src/components/ui/Button.vue`, `Input.vue`, `Label.vue`; `src/lib/utils.ts` with `cn()` helper
97. ✅ Create `src/lib/axios.ts` — base URL `http://backend.test/api`, Bearer token interceptor reads from localStorage
98. ✅ Create `src/router/index.ts` — routes for `/login`, `/classes`, `/classes/:id`; `vite.config.ts` and `tsconfig.app.json` updated with `@` path alias
99. ✅ Add router navigation guard — `requiresAuth` redirects to `/login`; `requiresGuest` redirects authenticated users to `/classes`
100. ✅ Create `src/stores/useAuthStore.ts` — Pinia store; `login()`, `logout()`, token + user persisted in localStorage; fetches full user (with tenant) after login
101. ✅ Create `LoginPage.vue` — email/password form; 401/422 error handling; redirects to `/classes` on success

---

## Phase 11 — Class Dashboard (Frontend)

**Goal:** Authenticated users can see a paginated, searchable list of classes.

**Architecture note:** The class list is held in a local `classList` ref inside `ClassDashboard.vue` — not a Pinia store. This ensures the list is always fresh on navigation (no stale data if another user creates a class). Year levels and staff users are fetched once and stored in a shared `useReferenceStore` Pinia store since they are seeded/rarely change and are needed by both the filter bar and the form dialog. All authenticated pages are wrapped in `AppSidebar.vue` — an 88px icon-only sidebar component (logo mark, `BookOpen` nav icon with active border, user initials avatar, `LogOut` icon) that wraps page content via `<slot />`.

102. ✅ Create `src/stores/useReferenceStore.ts` — Pinia store that fetches and caches year levels (`GET /api/year_levels`) and staff users (`GET /api/users`); `load()` guards with a `loaded` flag so it only hits the API once per session
103. ✅ Create `src/composables/useClasses.ts` — wraps all class API calls (list, create, update, delete); returns `classList` ref, `meta`, pagination and filter state; CRUD methods do NOT auto-refresh — `ClassDashboard` calls `fetchClasses()` explicitly so only one re-fetch happens per mutation
103a. ✅ Create `src/components/AppSidebar.vue` — 88px icon-only shared layout wrapper; `BookOpen` nav icon with `border-l-2 border-teal bg-white/10` active state; user initials avatar (`bg-teal-light text-teal`); `LogOut` icon at the bottom; `<slot />` for page content
104. ✅ Create `ClassDashboard.vue` page — wrapped in `<AppSidebar>`; local `classList` ref; stat cards (Total Classes, Total Students, Teachers Assigned); filter bar (search debounced 300ms, year level select, staff select, Clear filters); class table with RouterLink name, year level, staff, student count, edit/delete icons; pagination; delete confirmation via vue-sonner
105. ✅ Create `ClassFormDialog.vue` — 2-column modal (`max-w-3xl`, Teleported to body); left column: class name, staff checkboxes, year level select, selected student badge chips (teal-light, removable); right column: paginated student table (10 per page) with name/year-level filter, Plus/Check icons, "Showing X–Y of Z" footer
106. ✅ Wire up create class button → dialog → `POST /api/classes` → `fetchClasses()` refresh → success toast
107. ✅ Wire up edit button → dialog pre-populated via `GET /api/classes/{id}` (to retrieve enrolled student IDs) → `PUT /api/classes/{id}` → `fetchClasses()` refresh → success toast
108. ✅ Install `vue-sonner` → `npx shadcn-vue@latest add sonner`; add `<Sonner />` wrapper component and mount `<Sonner />` once in `App.vue`
109. ✅ Wire up delete button → `toast('Please confirm you want to delete Class "Name"', { duration: Infinity, action: { label: 'Yes, delete', onClick }, cancel: { label: 'No' } })`

**Outstanding — required to complete Phase 11:**

109a. ❌ Build `GET /api/students` backend endpoint — currently returns 404; needed by `ClassFormDialog` to populate the student picker on dialog open
  - Create `app/Http/Resources/StudentListResource.php` — shape: `{ id, full_name, given_name, family_name, year_level }`
  - Create `app/Policies/StudentPolicy.php` — `viewAny()` checks `view students` permission (already seeded, all roles have it)
  - Create `app/Repositories/StudentRepository.php` — `list(array $filters)`: query with `yearLevel` eager-loaded, ordered alphabetically by `family_name` then `given_name`, filtered by `search` and `year_level_id`, paginated with `per_page` defaulting to 100
  - Create `app/Http/Controllers/StudentController.php` — `index()`: authorize, call repository, return `StudentListResource::collection()`
  - Register route in `routes/api.php` inside `auth:sanctum` + `tenant` group: `Route::get('/students', [StudentController::class, 'index'])`

109b. ❌ Add alphabetical sort to `filteredStudents` computed in `ClassFormDialog.vue` — `.sort((a, b) => a.full_name.localeCompare(b.full_name))` (belt-and-suspenders: backend already orders, but client-side sort preserves order after filtering)

---

## Phase 12 — Class Detail Page + Notes (Frontend)

**Goal:** Clicking a class opens a two-pane view showing enrolled students, per-student NCCD data, notes, and bulk note creation. Phases 12 and 13 were merged — notes were built alongside the detail page in the same session.

**Backend changes made during this phase:**
- `NoteRepository::forStudent()` — `.latest()` replaced with `.orderBy('note_date', 'asc')`
- `ClassStudentResource` — added `date_of_birth`
- `ClassDetailResource` — added `updated_at`
- `SchoolClass::students()` — added `->orderBy('family_name')->orderBy('given_name')` at the relationship level

**New shadcn-vue components:** `Separator`, `Textarea`, `Checkbox`, `Tabs`, `TabsList`, `TabsTrigger`, `TabsContent`, `ScrollArea`, `Dialog`, `DialogTrigger`, `DialogContent`, `DialogHeader`, `DialogTitle`, `DialogFooter`, `DialogClose`

109. ✅ Create `src/composables/useClassDetail.ts` — `fetchClass`, `deleteClass`, `fetchNotes`, `saveNote`
110. ✅ Create `src/components/StudentListPanel.vue` — left-pane wrapper with `ScrollArea`; passes students to `StudentListItem` list
111. ✅ Create `src/components/StudentListItem.vue` — student row with purple Avatar, full name, NCCD badge, and highlighted-selected state
112. ✅ Create `src/components/StudentProfilePanel.vue` — right pane with xl Avatar, profile header (NCCD badges + metadata column), and Tabs (Notes / Strategies)
113. ✅ Create `src/components/NotesList.vue` — scrollable notes area; scrolls to bottom on update
114. ✅ Create `src/components/NoteCard.vue` — avatar outside bordered bubble; pencil placeholder icon with Tooltip
115. ✅ Create `src/components/NoteComposer.vue` — note entry form; visible only to `canAddNotes` roles
116. ✅ Create `src/components/StrategiesView.vue` — placeholder Card ("Strategy management will be available in a future update.")
117. ✅ Create `src/components/BulkNoteModal.vue` — Dialog with student checkbox selector and note form; submits to `POST /api/notes`
118. ✅ Create `src/pages/ClassDetailPage.vue` — orchestrator; breadcrumb; title + role-gated action buttons (Edit, Delete, Add Multiple Notes); 3 stat cards; two-pane `grid-cols-5 h-[700px]` layout; ClassFormDialog always rendered; BulkNoteModal
119. ✅ Add `hasRole()` function to `useAuthStore`
120. ✅ Add `canCreate`, `canEdit`, `canDelete`, `canAddNotes` computed properties to `useAuthStore` — destructured by pages rather than duplicated inline
121. ✅ Extract `getInitials()` to `src/lib/utils.ts` — shared across AppSidebar, StudentListItem, NoteCard
122. ✅ Add `xl` size variant to `Avatar.vue`
123. ✅ Add `HelpCircle` placeholder icon to `AppSidebar` below Settings
124. ✅ Apply `canCreate` / `canEdit` / `canDelete` role gates to ClassDashboard action buttons; rename "Add New Class" → "New Class" with Plus icon

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
