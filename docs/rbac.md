# RBAC — Roles and Permissions

Authorization is handled by **Spatie Laravel Permission** for role and permission definitions, and **Laravel Policies** for enforcing them at the resource level. All roles and permissions are seeded — there is no UI for managing them.

---

## Roles

| Role | Scope | Description |
|---|---|---|
| `school-admin` | Tenant | Full access within their school. Cannot access other schools. |
| `coordinator` | Tenant | Full class management — create, edit, and delete classes. |
| `teacher` | Tenant | Can view and edit all classes. Can add notes. |
| `teachers-assistant` | Tenant | Can view all classes and student data. Can add notes. Cannot create, edit, or delete classes. |
| `read-only` | Tenant | Can view classes and student data. Cannot create, edit, or delete anything. |

> There is no `super-admin` role in the application UI for this portfolio project. Super admin actions (creating tenants, seeding data) are done via Artisan/seeders.

---

## Permissions Matrix

| Permission | school-admin | coordinator | teacher | teachers-assistant | read-only |
|---|:---:|:---:|:---:|:---:|:---:|
| `view classes` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create class` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `edit class` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `delete class` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view students` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `add student note` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `view student notes` | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Policy Rules (Beyond Permissions)

Permissions control whether a user can perform an action at all. Policies apply additional rules at the resource level.

### ClassPolicy

| Method | Rule |
|---|---|
| `viewAny` | User has `view classes` permission |
| `view` | User has `view classes` permission |
| `create` | User has `create class` permission |
| `update` | User has `edit class` permission |
| `delete` | User has `delete class` permission |

### StudentNotePolicy

| Method | Rule |
|---|---|
| `create` | User has `add student note` permission |
| `viewAny` | User has `view student notes` permission |

---

## Spatie Setup

### Configuration

In `config/permission.php`, set the team feature to use `tenant_id` so roles are scoped per tenant in the single database:

```php
'teams' => true,
'team_foreign_key' => 'tenant_id',
```

When assigning roles, always pass the tenant ID:
```php
setPermissionsTeamId($user->tenant_id);
$user->assignRole('teacher');
```

When checking permissions, Stancl will have already initialised the tenant context, so the team ID is set automatically via middleware.

---

## Seeder

`database/seeders/RolesAndPermissionsSeeder.php` — runs as part of the tenant seeder.

```php
// All permissions
$permissions = [
    'view classes',
    'create class',
    'edit class',
    'delete class',
    'view students',
    'add student note',
    'view student notes',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
}

// Roles and their assigned permissions
$rolePermissions = [
    'school-admin'       => $permissions, // all
    'coordinator'        => ['view classes', 'create class', 'edit class', 'delete class',
                             'view students', 'add student note', 'view student notes'],
    'teacher'            => ['view classes', 'create class', 'edit class',
                             'view students', 'add student note', 'view student notes'],
    'teachers-assistant' => ['view classes', 'view students', 'add student note', 'view student notes'],
    'read-only'          => ['view classes', 'view students', 'view student notes'],
];

foreach ($rolePermissions as $roleName => $perms) {
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $role->syncPermissions($perms);
}
```

---

## How Authorization Is Called

### In Form Requests (permission-level check)
```php
public function authorize(): bool
{
    return $this->user()->can('create class');
}
```

### In Controllers (policy-level check)
```php
public function show(SchoolClass $class): ClassDetailResource
{
    $this->authorize('view', $class);

    return new ClassDetailResource($class->load(...));
}
```

### Never in Repositories or Models
Repositories return data. They do not check who is asking for it.
