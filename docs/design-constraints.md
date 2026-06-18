# Design Constraints

These are the agreed conventions and rules for this project. They exist to keep the codebase consistent, maintainable, and free of the anti-patterns present in the original Inspire implementation.

---

## Folder Structure

```
backend/
├── app/
│   ├── Enums/              PHP backed enums (NccdLevelEnum, NccdCategoryEnum)
│   ├── Http/
│   │   ├── Controllers/    One controller per resource
│   │   ├── Middleware/     Custom middleware (InitialiseTenantFromUser)
│   │   └── Requests/       Form Request classes
│   ├── Models/             Eloquent models
│   ├── Observers/          Model observers
│   ├── Policies/           Laravel policies
│   ├── Providers/          Service providers
│   ├── Repositories/       Repository classes
│   ├── Resources/          API Resource classes
│   └── Services/           Service classes
├── database/
│   ├── migrations/         All migrations (single database)
│   └── seeders/            TenantSeeder and related seeders
└── routes/
    └── api.php             All API routes
```

```
frontend/
├── src/
│   ├── components/         Reusable Vue components (including shadcn-vue)
│   ├── composables/        Vue composables (useAuth, useClasses, etc.)
│   ├── pages/              Route-level page components
│   ├── router/             Vue Router configuration
│   ├── stores/             Pinia stores
│   └── lib/                Utilities (axios instance, helpers)
```

---

## Naming Conventions

### Backend

| Thing | Convention | Example |
|---|---|---|
| Controllers | PascalCase, singular, suffixed | `ClassController` |
| Services | PascalCase, singular, suffixed | `ClassService` |
| Repositories | PascalCase, singular, suffixed | `ClassRepository` |
| Form Requests | Verb + Resource + Request | `StoreClassRequest`, `UpdateClassRequest` |
| API Resources | Resource + context + Resource | `ClassListResource`, `ClassDetailResource` |
| Policies | Resource + Policy | `ClassPolicy` |
| Observers | Resource + Observer | `ClassObserver` |
| Models | PascalCase, singular | `SchoolClass`, `Student` |
| Database tables | snake_case, plural | `classes`, `student_notes` |
| Migrations | Laravel default timestamp prefix | `2026_06_01_000001_create_classes_table` |
| Route names | dot notation, plural resource | `classes.index`, `classes.store` |

### Frontend

| Thing | Convention | Example |
|---|---|---|
| Page components | PascalCase, descriptive | `ClassDashboard.vue`, `ClassDetail.vue` — all `.vue` files use `<script setup lang="ts">` |
| Shared components | PascalCase | `BulkNoteModal.vue`, `StudentPanel.vue` |
| Composables | camelCase, `use` prefix | `useClasses.js`, `useAuth.js` |
| Pinia stores | camelCase, `use` prefix | `useClassStore.js`, `useAuthStore.js` |
| API calls | Inside composables only | Never in components or pages directly |

---

## Layer Rules

### Controllers must not:
- Write Eloquent queries
- Contain `if` / business logic beyond routing
- Return raw arrays — use API Resources

### Services must not:
- Return query builders
- Perform authorization checks
- Know about HTTP (no `request()`, no `response()`)

### Repositories must not:
- Contain authorization logic
- Know about the current user
- Return anything other than models, collections, or paginators

### Models must not:
- Call services
- Perform authorization
- Contain business logic beyond scopes and accessors

### API Resources must not:
- Query the database
- Call services
- Contain business logic beyond display formatting

### Policies must not:
- Query data beyond what is needed for the authorization decision
- Call services

---

## Authorization Rules

- All permission checks use `$this->authorize()` in controllers or `$this->user()->can()` in Form Requests
- Never check permissions in repositories, models, or services
- All resource-level rules (e.g. "teacher can only see assigned classes") live in Policies
- Permission-level rules (e.g. "user must have `create class`") live in Form Requests

---

## Tenancy Rules

- Never manually add `tenant_id` to a query — Stancl handles this via `BelongsToTenant`
- Never bypass the tenant scope with `withoutTenancy()` unless there is an explicit documented reason
- The `InitialiseTenantFromUser` middleware must run on every authenticated route
- Tenant context is always set before any tenant-scoped query runs

---

## API Response Rules

- All list endpoints return paginated responses with a `data` array and `meta` object
- All single-resource responses wrap the object in a `data` key
- All success/delete responses return a `message` string
- Error responses follow Laravel's default validation error format (422) and auth error format (401/403)
- Never return raw Eloquent models from controllers — always use an API Resource

---

## Frontend Rules

- All frontend files use TypeScript (`<script setup lang="ts">`) — no plain `.js` files in `frontend/src/`
- All API calls are made inside composables or Pinia stores — never directly in page or component files
- The Axios instance is configured once in `src/lib/axios.js` with the base URL and auth interceptor
- The auth token is stored in `localStorage` and attached to all requests via an Axios request interceptor
- Vue Router guards redirect unauthenticated users to `/login`
- Components receive data via props — they do not fetch their own data

---

## What Belongs in the Seeder (Not the Application)

The following are **seeder concerns only** and have no corresponding API endpoints or UI:

- Creating tenants
- Creating users (school staff)
- Creating students with NCCD data
- Assigning roles to users
- Creating year levels

The application UI manages only:
- Classes (CRUD)
- Staff assignment to classes
- Student enrolment in classes
- Student notes
