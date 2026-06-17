# API Contracts

All tenant API routes are prefixed with `/api` and served from the single Railway domain (e.g. `yourapp.railway.app/api`). The tenant context is resolved from the authenticated user's `tenant_id` via the `InitialiseTenantFromUser` middleware, which runs after `auth:sanctum`.

**Base URL:** `https://yourapp.railway.app/api`

**Authentication:** All routes except login require `Authorization: Bearer {token}`.

> **Scope note:** Tenants, users, and students are created exclusively via database seeders. There are no API endpoints or UI screens for creating or managing these records. The application is concerned only with class management, enrolment, and notes over the seeded data.

---

## Authentication

### POST /api/login
Authenticate a user and return a Sanctum Bearer token.

**Middleware:** none (public)

**Request**
```json
{
  "email": "teacher@springfieldprimary.edu.au",
  "password": "secret"
}
```

**Validation**
| Field | Rules |
|---|---|
| email | required, string, email |
| password | required, string |

**Response 200**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Jane Smith",
    "email": "teacher@springfieldprimary.edu.au",
    "roles": ["teacher"]
  }
}
```

**Response 422** — validation failed
**Response 401** — credentials do not match

---

### POST /api/logout
Revoke the current user's token.

**Middleware:** `auth:sanctum`

**Request:** none

**Response 200**
```json
{
  "message": "Logged out successfully."
}
```

---

### GET /api/user
Return the currently authenticated user with their roles.

**Middleware:** `auth:sanctum`, `tenant`

**Response 200**
```json
{
  "id": 1,
  "name": "Jane Smith",
  "email": "teacher@springfieldprimary.edu.au",
  "roles": ["teacher"],
  "tenant": {
    "id": "01j...",
    "name": "Springfield Primary School"
  }
}
```

---

## Classes

### GET /api/classes
List classes for the current tenant. Supports search and pagination.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `view classes`

**Query Parameters**
| Param | Type | Description |
|---|---|---|
| search | string | Filter by class name |
| page | integer | Page number (default: 1) |
| per_page | integer | Results per page (default: 15) |

**Response 200**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Year 9 Science",
      "year_level": { "id": 9, "description": "Year 9" },
      "created_by": { "id": 1, "name": "Jane Smith" },
      "assigned_users": [
        { "id": 1, "name": "Jane Smith", "roles": ["teacher"] }
      ],
      "student_count": 24
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

---

### POST /api/classes
Create a new class.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `create class`

**Request**
```json
{
  "name": "Year 9 Science",
  "year_level_id": 9,
  "user_ids": [1, 2],
  "student_ids": [10, 11, 12]
}
```

**Validation**
| Field | Rules |
|---|---|
| name | required, string, max:255 |
| year_level_id | nullable, integer, exists:year_levels,id |
| user_ids | nullable, array |
| user_ids.* | integer, exists:users,id |
| student_ids | nullable, array |
| student_ids.* | integer, exists:students,id |

**Response 201**
```json
{
  "data": {
    "id": 5,
    "name": "Year 9 Science",
    "year_level": { "id": 9, "description": "Year 9" },
    "created_by": { "id": 1, "name": "Jane Smith" },
    "assigned_users": [],
    "students": []
  }
}
```

---

### GET /api/classes/{class}
Get full detail for a single class including enrolled students with NCCD data.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `view classes`
**Policy:** `ClassPolicy@view` — teachers/assistants can only view assigned classes

**Response 200**
```json
{
  "data": {
    "id": 1,
    "name": "Year 9 Science",
    "year_level": { "id": 9, "description": "Year 9" },
    "created_by": { "id": 1, "name": "Jane Smith" },
    "assigned_users": [
      { "id": 1, "name": "Jane Smith", "roles": ["teacher"] }
    ],
    "nccd_summary": {
      "QDTP": 2,
      "Supplementary": 5,
      "Substantial": 3,
      "Extensive": 1
    },
    "students": [
      {
        "id": 10,
        "full_name": "Bart Simpson",
        "given_name": "Bart",
        "family_name": "Simpson",
        "year_level": { "id": 4, "description": "Year 4" },
        "nccd_level": "Supplementary",
        "nccd_category": "Social/Emotional",
        "primary_disability": "ADHD",
        "primary_disability_level_formalised": true
      }
    ]
  }
}
```

---

### PUT /api/classes/{class}
Update a class name and/or year level.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `edit class`
**Policy:** `ClassPolicy@update`

**Request**
```json
{
  "name": "Year 9 Advanced Science",
  "year_level_id": 9
}
```

**Validation**
| Field | Rules |
|---|---|
| name | required, string, max:255 |
| year_level_id | nullable, integer, exists:year_levels,id |

**Response 200** — same shape as GET /api/classes/{class}

---

### DELETE /api/classes/{class}
Soft-delete a class (sets `deleted_at`).

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `delete class`
**Policy:** `ClassPolicy@delete`

**Response 200**
```json
{
  "message": "Class deleted successfully."
}
```

---

## Class Staff Assignment

### POST /api/classes/{class}/users
Assign one or more staff members to a class.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `edit class`

**Request**
```json
{
  "user_ids": [2, 3]
}
```

**Validation**
| Field | Rules |
|---|---|
| user_ids | required, array, min:1 |
| user_ids.* | integer, exists:users,id |

**Response 200**
```json
{
  "message": "Staff assigned successfully."
}
```

---

### DELETE /api/classes/{class}/users/{user}
Remove a staff member from a class.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `edit class`

**Response 200**
```json
{
  "message": "Staff removed successfully."
}
```

---

## Class Student Enrolment

### POST /api/classes/{class}/students
Enrol one or more students into a class.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `edit class`

**Request**
```json
{
  "student_ids": [10, 11, 12]
}
```

**Validation**
| Field | Rules |
|---|---|
| student_ids | required, array, min:1 |
| student_ids.* | integer, exists:students,id |

**Response 200**
```json
{
  "message": "Students enrolled successfully."
}
```

---

### DELETE /api/classes/{class}/students/{student}
Remove a student from a class.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `edit class`

**Response 200**
```json
{
  "message": "Student removed successfully."
}
```

---

## Students

### GET /api/students
Search and list students. Used by the enrolment picker in the class form.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `view students`

**Query Parameters**
| Param | Type | Description |
|---|---|---|
| search | string | Filter by name |
| year_level_id | integer | Filter by year level |
| page | integer | Page number |

**Response 200**
```json
{
  "data": [
    {
      "id": 10,
      "full_name": "Bart Simpson",
      "given_name": "Bart",
      "family_name": "Simpson",
      "year_level": { "id": 4, "description": "Year 4" }
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 8 }
}
```

---

## Student Notes

### GET /api/students/{student}/notes
List notes for a student, optionally scoped to a class.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `view student notes`

**Query Parameters**
| Param | Type | Description |
|---|---|---|
| class_id | integer | Filter notes to a specific class |

**Response 200**
```json
{
  "data": [
    {
      "id": 1,
      "note_text": "Bart was particularly engaged today.",
      "note_date": "2026-06-16",
      "note_type": null,
      "confidentiality_level": null,
      "author": { "id": 1, "name": "Jane Smith" },
      "class": { "id": 1, "name": "Year 9 Science" },
      "created_at": "2026-06-16T09:00:00Z"
    }
  ]
}
```

---

### POST /api/notes
Create a note for one or more students (bulk note creation). If `student_ids` contains multiple values, one `StudentNote` record is created per student with identical content.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `add student note`
**Policy:** `StudentNotePolicy@create`

**Request**
```json
{
  "student_ids": [10, 11, 12],
  "class_id": 1,
  "note_text": "All three students completed the assessment successfully.",
  "note_date": "2026-06-16",
  "note_type": null,
  "confidentiality_level": null
}
```

**Validation**
| Field | Rules |
|---|---|
| student_ids | required, array, min:1 |
| student_ids.* | integer, exists:students,id |
| class_id | required, integer, exists:classes,id |
| note_text | required, string, max:5000 |
| note_date | required, date |
| note_type | nullable, string, max:100 |
| confidentiality_level | nullable, string, max:100 |

**Response 201**
```json
{
  "message": "Notes created for 3 student(s).",
  "count": 3
}
```

---

## Users (Staff Picker)

### GET /api/users
List users in the current tenant. Used by the class create/edit form to populate the staff assignment picker. Read-only — users are created via seeders only.

**Middleware:** `auth:sanctum`, `tenant`
**Permission:** `edit class`

**Query Parameters**
| Param | Type | Description |
|---|---|---|
| search | string | Filter by name |

**Response 200**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Jane Smith",
      "roles": ["teacher"]
    }
  ]
}
```

