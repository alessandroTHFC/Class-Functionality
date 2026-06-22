<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Http\Resources\ClassDetailResource;
use App\Http\Resources\ClassListCollection;
use App\Models\SchoolClass;
use App\Services\ClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Inject ClassService via constructor.
     *
     * Design pattern: the controller is thin — it receives the HTTP request, delegates
     * all work to the service, and returns a resource or JSON response. No Eloquent
     * queries or business logic belong here.
     */
    public function __construct(private readonly ClassService $service) {}

    /**
     * Return a paginated list of classes with the tenant-wide summary in meta.
     *
     * $this->authorize('viewAny', SchoolClass::class) calls ClassPolicy::viewAny() which
     * checks the 'view classes' permission. Passing the class name (not an instance) tells
     * Laravel this is an "any" check — no specific resource is being acted on.
     *
     * Laravel concept: query parameters are read from $request->only() and passed as a
     * plain array to the service. The repository decides how to apply each filter.
     *
     * ClassListCollection is a custom ResourceCollection that injects the summary into
     * the meta block of the paginated JSON response via paginationInformation().
     */
    public function index(Request $request): ClassListCollection
    {
        $this->authorize('viewAny', SchoolClass::class);

        $result = $this->service->list($request->only([
            'search', 'user_id', 'year_level_id', 'per_page',
        ]));

        return new ClassListCollection($result['paginator'], $result['summary']);
    }

    /**
     * Create a new class and return a 201 message response.
     *
     * Authorization: StoreClassRequest::authorize() checks the 'create class' permission
     * before validation runs. If the user lacks the permission, Laravel returns a 403
     * without reaching this method.
     *
     * The response is a plain message (not the new class) because the frontend re-fetches
     * GET /api/classes after a successful create to refresh the list.
     */
    public function store(StoreClassRequest $request): JsonResponse
    {
        $this->service->create($request->validated());

        return response()->json(['message' => 'Class created successfully.'], 201);
    }

    /**
     * Return the full detail for a single class.
     *
     * Route model binding: Laravel resolves {class} in the URL into a SchoolClass instance
     * automatically. Because SchoolClass uses BelongsToTenant, the global scope ensures
     * a class from another tenant returns 404, not the real record.
     *
     * $this->authorize('view', $class) calls ClassPolicy::view() as a resource-level check.
     */
    public function show(SchoolClass $class): ClassDetailResource
    {
        $this->authorize('view', $class);

        $class = $this->service->find($class->id);

        return new ClassDetailResource($class);
    }

    /**
     * Update a class and sync staff/students, returning the updated detail.
     *
     * Authorization is two-layered:
     * - UpdateClassRequest::authorize() checks the 'edit class' permission (returns 403
     *   before validation if the user can't edit).
     * - $this->authorize('update', $class) calls ClassPolicy::update() as the policy check.
     *
     * Students and staff are added and removed entirely through this update flow. Submitting
     * an empty array for user_ids or student_ids removes all assignments. The frontend always
     * sends the full desired state from the multi-select.
     *
     * The response returns the full updated class detail so the frontend can update its
     * local state without a separate GET request.
     */
    public function update(UpdateClassRequest $request, SchoolClass $class): ClassDetailResource
    {
        $this->authorize('update', $class);

        $updated = $this->service->update($class, $request->validated());

        return new ClassDetailResource($updated);
    }

    /**
     * Soft-delete a class.
     *
     * ClassPolicy::delete() checks 'delete class' — teachers cannot delete, only
     * school-admins and coordinators can.
     *
     * After deletion, deleted_at is set and the record is excluded from all future queries
     * by Eloquent's global soft-delete scope. The class is not permanently removed.
     */
    public function destroy(SchoolClass $class): JsonResponse
    {
        $this->authorize('delete', $class);

        $this->service->delete($class);

        return response()->json(['message' => 'Class deleted successfully.']);
    }
}
