<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentListResource;
use App\Models\Student;
use App\Repositories\StudentRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController extends Controller
{
    /**
     * Inject StudentRepository via constructor.
     *
     * Design pattern: the controller is thin — it receives the HTTP request, delegates
     * all query logic to the repository, and returns a resource. No Eloquent queries
     * or business logic belong here.
     */
    public function __construct(private readonly StudentRepository $repository) {}

    /**
     * Return a paginated list of students for the current tenant.
     *
     * Used exclusively by the class form dialog's enrolment picker. The dialog sends
     * per_page=100 to retrieve all students in one request for client-side filtering.
     *
     * $this->authorize('viewAny', Student::class) calls StudentPolicy::viewAny(), which
     * checks the 'view students' permission. All five roles hold this permission, so
     * every authenticated user passes. Passing the class name (not an instance) tells
     * Laravel this is an "any" check — no specific resource is being acted on.
     *
     * BelongsToTenant on Student scopes the query to the current tenant automatically,
     * so no cross-tenant data can leak regardless of the filters passed in.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);

        $students = $this->repository->list($request->only([
            'search', 'year_level_id', 'per_page',
        ]));

        return StudentListResource::collection($students);
    }
}
