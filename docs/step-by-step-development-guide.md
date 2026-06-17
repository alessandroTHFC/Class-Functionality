# Step-by-Step Development Guide

This is the sequential build roadmap. Each step should be completed and verified before moving to the next. Steps within a phase that are independent can be done in any order, but phases must be completed in sequence.

---

## Phase 1 — Project Scaffolding

**Goal:** Get both applications running locally with a working database connection.

1. Scaffold the Laravel project inside the repo root: `laravel new backend --no-interaction`
2. Add `docker-compose.yml` at the repo root with a MySQL 8 service
3. Start Docker: `docker compose up -d`
4. Configure `backend/.env` — set `DB_*` variables to point at Docker MySQL
5. Run `php artisan migrate` — confirm the default Laravel tables are created
6. Connect SQLTools in VS Code to the Docker MySQL instance and confirm you can see the database
7. Scaffold the Vue project: `npm create vite@latest frontend -- --template vue`
8. Confirm both `backend/` and `frontend/` exist under the repo root

---

## Phase 2 — Install and Configure Packages

**Goal:** All major packages installed and minimally configured.

9. Install Sanctum: `composer require laravel/sanctum` → run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
10. Install Stancl Tenancy: `composer require stancl/tenancy` → run `php artisan vendor:publish --tag=tenancy-config`
11. Install Spatie Permission: `composer require spatie/laravel-permission` → run `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
12. Install DomPDF: `composer require barryvdh/laravel-dompdf`
13. Configure Sanctum guard in `config/auth.php` — set API guard to use Sanctum
14. Configure CORS in `config/cors.php` — allow requests from the Vue dev server origin (`http://localhost:5173`)

---

## Phase 3 — Tenancy Configuration

**Goal:** Stancl configured for single-database mode with tenant-from-user resolution.

15. Configure `config/tenancy.php` — set single-database mode, register tenant-aware models
16. Enable Spatie teams feature in `config/permission.php` — set `teams => true`, `team_foreign_key => tenant_id`
17. Create `InitialiseTenantFromUser` middleware in `app/Http/Middleware/`
18. Register the middleware in `bootstrap/app.php`
19. Define the `tenant` middleware group in route configuration

---

## Phase 4 — Database Migrations

**Goal:** All tables created in the correct order with proper foreign keys.

20. Create migration: `tenants` table (Stancl publishes this — verify it exists)
21. Create migration: `domains` table (Stancl publishes this — verify it exists)
22. Create migration: `users` table — add `tenant_id` column, ensure `email` has a global unique index (not scoped)
23. Create migration: `year_levels` table with `tenant_id`
24. Create migration: `classes` table with `tenant_id` and `deleted_at`
25. Create migration: `class_users` pivot table with `tenant_id`
26. Create migration: `class_students` pivot table with `tenant_id`
27. Create migration: `students` table with `tenant_id`, NCCD enum columns, and `deleted_at`
28. Create migration: `student_notes` table with `tenant_id` and `deleted_at`
29. Run `php artisan migrate` — verify all tables appear in SQLTools
30. Publish and run Spatie permission migrations

---

## Phase 5 — Eloquent Models and Enums

**Goal:** All models defined with correct relationships, traits, and casts.

31. Create `app/Enums/NccdLevelEnum.php`
32. Create `app/Enums/NccdCategoryEnum.php`
33. Create/update `User` model — add `BelongsToTenant`, `HasRoles`, `SoftDeletes`, relationships
34. Create `YearLevel` model — add `BelongsToTenant`, relationships
35. Create `SchoolClass` model — add `BelongsToTenant`, `SoftDeletes`, relationships, scopes
36. Create `ClassUser` model — add `BelongsToTenant`
37. Create `ClassStudent` model — add `BelongsToTenant`
38. Create `Student` model — add `BelongsToTenant`, `SoftDeletes`, relationships, casts, `full_name` accessor
39. Create `StudentNote` model — add `BelongsToTenant`, `SoftDeletes`, relationships, casts
40. Create `Tenant` model — add `domains()` relationship

---

## Phase 6 — Seeders

**Goal:** A single `php artisan db:seed` creates one fully-populated demo school.

41. Create `RolesAndPermissionsSeeder` — seeds all roles and permissions (see `docs/rbac.md`)
42. Create `TenantSeeder` — creates the demo tenant, initialises tenancy context, calls sub-seeders
43. Create `UserSeeder` — creates one user per role (coordinator, teacher, teachers-assistant, read-only, school-admin) with realistic names and emails
44. Create `YearLevelSeeder` — seeds Foundation through Year 12
45. Create `StudentSeeder` — seeds ~30 students with varied NCCD data across year levels
46. Create `ClassSeeder` — seeds 3–5 classes with students enrolled and staff assigned
47. Register all seeders in `DatabaseSeeder.php`
48. Run `php artisan db:seed` and verify data in SQLTools

---

## Phase 7 — Authentication

**Goal:** Login and logout endpoints working, returning a valid Sanctum token.

49. Create `AuthController` with `login`, `logout`, and `user` methods
50. Register auth routes in `routes/api.php` (public — no middleware)
51. Test `POST /api/login` with a seeded user's credentials using a REST client (e.g. Postman or VS Code REST Client)
52. Confirm the token is returned, and that `GET /api/user` with the token returns the correct user and tenant
53. Confirm `POST /api/logout` revokes the token

---

## Phase 8 — Class Feature (Backend)

**Goal:** All class API endpoints implemented, tested, and returning correct API Resources.

54. Create `ClassPolicy` in `app/Policies/`
55. Register `ClassPolicy` in `AuthServiceProvider`
56. Create `ClassRepository` in `app/Repositories/`
57. Create `ClassService` in `app/Services/`
58. Create `StoreClassRequest` and `UpdateClassRequest` in `app/Http/Requests/`
59. Create `ClassListResource` and `ClassDetailResource` in `app/Resources/`
60. Create `ClassStudentResource` in `app/Resources/`
61. Create `ClassController` in `app/Http/Controllers/`
62. Register class routes in `routes/api.php` under the `tenant` middleware group
63. Create `ClassObserver` and register it in `AppServiceProvider`
64. Test all endpoints: GET /classes, POST /classes, GET /classes/{id}, PUT /classes/{id}, DELETE /classes/{id}
65. Test staff assignment: POST /classes/{id}/users, DELETE /classes/{id}/users/{userId}
66. Test student enrolment: POST /classes/{id}/students, DELETE /classes/{id}/students/{studentId}

---

## Phase 9 — Student Notes (Backend)

**Goal:** Note creation endpoint working, including bulk (multiple students, one request).

67. Create `StudentNotePolicy` in `app/Policies/`
68. Create `NoteRepository` in `app/Repositories/`
69. Create `NoteService` in `app/Services/` — implement bulk note creation loop
70. Create `StoreNoteRequest` in `app/Http/Requests/`
71. Create `StudentNoteResource` in `app/Resources/`
72. Create `NoteController` in `app/Http/Controllers/`
73. Register note routes in `routes/api.php`
74. Test `GET /api/students/{id}/notes`
75. Test `POST /api/notes` with a single student
76. Test `POST /api/notes` with multiple student IDs — confirm one record per student is created

---

## Phase 10 — Reports (Backend)

**Goal:** PDF class report downloadable via the API.

77. Create a Blade template for the PDF report: `resources/views/reports/class-report.blade.php`
78. Create `ClassReportBuilder` in `app/Services/` (or a dedicated `app/Reports/` folder)
79. Add `generateReport` method to `ClassController`
80. Register the report route in `routes/api.php`
81. Test `GET /api/classes/{id}/report` — confirm a PDF downloads correctly

---

## Phase 11 — Vue SPA Setup

**Goal:** Vue app running, connected to the Laravel API, with routing and auth store in place.

82. Install frontend dependencies: `npm install vue-router@4 pinia axios`
83. Install and configure Tailwind CSS in the frontend
84. Set up shadcn-vue — copy initial base components (Button, Dialog, Input, etc.)
85. Create `src/lib/axios.js` — configure base URL (`http://localhost:8000`) and Bearer token interceptor
86. Create `src/router/index.js` — define routes for `/login`, `/classes`, `/classes/:id`
87. Add router guard — redirect unauthenticated users to `/login`
88. Create `src/stores/useAuthStore.js` — handles login, logout, token persistence in localStorage
89. Create `LoginPage.vue` — email/password form that calls the login endpoint and stores the token

---

## Phase 12 — Class Dashboard (Frontend)

**Goal:** Authenticated users can see a paginated, searchable list of classes.

90. Create `src/stores/useClassStore.js` — fetches class list, handles pagination state
91. Create `src/composables/useClasses.js` — wraps API calls (list, create, update, delete)
92. Create `ClassDashboard.vue` page — table/card list of classes with search input and pagination
93. Create `ClassFormDialog.vue` — modal for create and edit; includes year level select, staff multi-select, student multi-select (all from seeded data via API)
94. Wire up create class button → dialog → `POST /api/classes`
95. Wire up edit button → dialog pre-populated → `PUT /api/classes/{id}`
96. Wire up delete button → confirmation → `DELETE /api/classes/{id}`

---

## Phase 13 — Class Detail (Frontend)

**Goal:** Clicking a class opens a two-pane view showing enrolled students and per-student data.

97. Create `ClassDetail.vue` page — two-pane layout (student list left, student detail panel right)
98. Create `StudentList.vue` component — lists enrolled students, clicking one selects them
99. Create `StudentPanel.vue` component — shows selected student's NCCD data and a Notes tab
100. Fetch class detail via `GET /api/classes/{id}` and display the NCCD summary counts in the header

---

## Phase 14 — Student Notes (Frontend)

**Goal:** Staff can view existing notes and create new ones, including bulk creation.

101. Create `NotesList.vue` component — displays notes inside the StudentPanel Notes tab
102. Create `BulkNoteModal.vue` — multi-student selector + note form; submits to `POST /api/notes`
103. Wire up "Add Note" button in StudentPanel for single-student note creation
104. Wire up "Bulk Note" button in ClassDetail for bulk note creation across selected students
105. Refresh notes list after submission

---

## Phase 15 — Reports (Frontend)

**Goal:** Coordinators and admins can download a class PDF report.

106. Add "Generate Report" button to `ClassDetail.vue` (visible only to users with `generate report` permission)
107. On click, make a GET request to `/api/classes/{id}/report` with `responseType: 'blob'` and trigger a browser download

---

## Phase 16 — Feature Tests (Backend)

**Goal:** Key API behaviour is covered by automated tests.

108. Create `tests/Feature/AuthTest.php` — test login success, login failure, logout
109. Create `tests/Feature/ClassTest.php` — test list, create, show, update, delete; test permission enforcement (e.g. read-only cannot create)
110. Create `tests/Feature/NoteTest.php` — test single note creation, bulk note creation, tenant isolation (notes from one tenant not visible to another)
111. Run `php artisan test` — all tests pass

---

## Phase 17 — Deployment to Railway

**Goal:** Application running on Railway, accessible via a public URL.

112. Create a Railway project and provision a MySQL service
113. Configure environment variables in Railway for the Laravel backend (`DB_*`, `APP_KEY`, `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`)
114. Deploy the Laravel backend to Railway (connect GitHub repo, set root to `backend/`)
115. Run `php artisan migrate --seed` on Railway via the Railway console
116. Build the Vue SPA: `npm run build` — update the Axios base URL to the Railway backend URL
117. Deploy the Vue frontend to Railway (or Vercel/Netlify — any static host)
118. Smoke test: login, view classes, create a note, generate a report

---

## Phase 18 — Documentation

**Goal:** GitHub repo is presentable to Beliven.

119. Write `README.md` at the repo root — project overview, setup instructions, tech stack, architectural decision rationale
120. Review and finalise all files in `docs/`
121. Commit all documentation
