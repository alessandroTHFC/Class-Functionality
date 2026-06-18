# Architecture

## Layer Overview

Every request passes through the following layers in order. Each layer has a single responsibility and strict rules about what it must not do.

```
HTTP Request
    │
    ▼
Middleware          — identity, tenant context, authentication
    │
    ▼
Form Request        — input validation and authorisation
    │
    ▼
Controller          — orchestrates the response, nothing more
    │
    ▼
Service             — business logic and orchestration
    │
    ▼
Repository          — all database queries
    │
    ▼
Model               — relationships, casts, scopes
    │
    ▼
API Resource        — shapes the JSON response
    │
    ▼
HTTP Response
```

---

## Middleware

**Responsibility:** Resolve the request context — who is making the request and which tenant they belong to.

**Must not:** Contain business logic, query application data, or return application responses.

**Stack applied to all authenticated tenant routes:**
1. `auth:sanctum` — verifies the Bearer token and sets `Auth::user()`
2. `InitialiseTenantFromUser` — reads `Auth::user()->tenant_id` and calls `tenancy()->initialize($tenant)`, which activates Stancl's global scope for the rest of the request

**Example — `InitialiseTenantFromUser` middleware:**
```php
class InitialiseTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        tenancy()->initialize($tenant);

        return $next($request);
    }
}
```

---

## Form Request

**Responsibility:** Validate incoming data and (optionally) perform policy-based authorisation before the controller is reached.

**Must not:** Call services, query the database directly, or shape responses.

**Example — `StoreClassRequest`:**
```php
class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create class');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'year_level_id' => ['nullable', 'integer', 'exists:year_levels,id'],
            'user_ids'      => ['nullable', 'array'],
            'user_ids.*'    => ['integer', 'exists:users,id'],
            'student_ids'   => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }
}
```

---

## Controller

**Responsibility:** Accept a validated request, call one service method, return one API Resource. Nothing else.

**Must not:** Contain business logic, write Eloquent queries, or shape data manually (use Resources).

**Example — `ClassController`:**
```php
class ClassController extends Controller
{
    public function __construct(private ClassService $classService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $classes = $this->classService->list($request->only('search', 'user_id', 'year_level_id', 'per_page'));

        return ClassListResource::collection($classes);
    }

    public function store(StoreClassRequest $request): JsonResponse
    {
        $this->classService->create($request->validated());

        return response()->json(['message' => 'Class created successfully.'], 201);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        $this->authorize('delete', $class);
        $this->classService->delete($class);

        return response()->json(['message' => 'Class deleted successfully.']);
    }
}
```

---

## Service

**Responsibility:** Own all business logic. Orchestrate repository calls, enforce business rules, trigger side effects (via Observers or direct dispatch).

**Must not:** Return Eloquent query builders, write HTTP responses, or perform authorization checks.

**Example — `ClassService`:**
```php
class ClassService
{
    public function __construct(private ClassRepository $classRepository) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->classRepository->paginate($filters);
    }

    public function summary(): array
    {
        return $this->classRepository->tenantSummary();
    }

    public function create(array $data): SchoolClass
    {
        $class = $this->classRepository->create([
            'name'              => $data['name'],
            'year_level_id'     => $data['year_level_id'] ?? null,
            'created_by_user_id' => Auth::id(),
        ]);

        if (!empty($data['user_ids'])) {
            $this->classRepository->syncUsers($class, $data['user_ids']);
        }

        if (!empty($data['student_ids'])) {
            $this->classRepository->syncStudents($class, $data['student_ids']);
        }

        return $class->load(['yearLevel', 'createdBy', 'users', 'students']);
    }

    public function delete(SchoolClass $class): void
    {
        $this->classRepository->delete($class);
    }
}
```

---

## Repository

**Responsibility:** Encapsulate all Eloquent queries. Every query in the application lives here.

**Must not:** Contain authorization logic, business rules, or knowledge of HTTP.

**Example — `ClassRepository`:**
```php
class ClassRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return SchoolClass::query()
            ->with(['yearLevel', 'createdBy', 'users'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['year_level_id'] ?? null, fn ($q, $v) => $q->where('year_level_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->whereHas('users', fn ($u) => $u->where('users.id', $v)))
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): SchoolClass
    {
        return SchoolClass::create($data);
    }

    public function syncUsers(SchoolClass $class, array $userIds): void
    {
        $class->users()->sync($userIds);
    }

    public function syncStudents(SchoolClass $class, array $studentIds): void
    {
        $class->students()->sync($studentIds);
    }

    public function delete(SchoolClass $class): void
    {
        $class->delete();
    }

    public function tenantSummary(): array
    {
        return [
            'total_students'    => Student::whereHas('classes')->count(),
            'teachers_assigned' => User::whereHas('assignedClasses')->count(),
        ];
    }
}
```

---

## Model

**Responsibility:** Define the database structure, relationships, casts, and reusable query scopes. Models are passive data containers.

**Must not:** Contain business logic, authorization, or service calls.

**Example — `SchoolClass`:**
```php
class SchoolClass extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $table = 'classes';

    protected $fillable = ['name', 'year_level_id', 'created_by_user_id'];

    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_users');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_students');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
    }
}
```

---

## API Resource

**Responsibility:** Shape the JSON response. Decide exactly which fields are included and how they are formatted. Each response shape gets its own Resource class.

**Must not:** Query the database, call services, or contain conditional business logic beyond display formatting.

**Example — `ClassListResource`** (lightweight, for the dashboard):
```php
class ClassListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'year_level'     => $this->whenLoaded('yearLevel', fn () => [
                'id'          => $this->yearLevel->id,
                'description' => $this->yearLevel->description,
            ]),
            'created_by'     => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ],
            'student_count'  => $this->students_count ?? 0,
        ];
    }
}
```

**Example — `ClassDetailResource`** (full data, for the class page):
```php
class ClassDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'year_level'     => $this->whenLoaded('yearLevel', ...),
            'created_by'     => [...],
            'assigned_users' => UserResource::collection($this->whenLoaded('users')),
            'nccd_summary'   => $this->nccdSummary(),
            'students'       => ClassStudentResource::collection($this->whenLoaded('students')),
        ];
    }

    private function nccdSummary(): array
    {
        return $this->students
            ->groupBy('nccd_level')
            ->map->count()
            ->toArray();
    }
}
```

---

## Observer

**Responsibility:** React to model lifecycle events and trigger side effects without polluting the service layer.

**Example — `ClassObserver`:**
```php
class ClassObserver
{
    public function created(SchoolClass $class): void
    {
        // Notify assigned staff that a class has been created
        // Stubbed as a log entry for this project
        Log::info("Class created: {$class->name}", ['class_id' => $class->id]);
    }
}
```

Registered in `AppServiceProvider::boot()`:
```php
SchoolClass::observe(ClassObserver::class);
```

