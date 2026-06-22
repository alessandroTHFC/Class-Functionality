<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Resources\StudentNoteResource;
use App\Models\Student;
use App\Models\StudentNote;
use App\Services\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $noteService) {}

    // List all notes for a student. Route model binding resolves {student} to a Student
    // instance — BelongsToTenant's global scope ensures the student belongs to the current
    // tenant, so a 404 is returned automatically if the ID belongs to another tenant.
    //
    // The optional ?class_id query parameter is forwarded to the service to filter notes
    // to a specific class. This supports the frontend's "view notes in context" view.
    //
    // Policy check: $this->authorize('viewAny', StudentNote::class) calls
    // StudentNotePolicy::viewAny() — it checks 'view student notes'.
    public function index(Request $request, Student $student): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StudentNote::class);

        $classId = $request->integer('class_id') ?: null;
        $notes   = $this->noteService->forStudent($student->id, $classId);

        return StudentNoteResource::collection($notes);
    }

    // Bulk-create one StudentNote per student_id. All validation and authorization
    // happens in StoreNoteRequest — the controller only calls the service and returns
    // the count in the response message.
    //
    // Returns 201 Created with a count so the frontend can display "Notes created for N student(s)."
    public function store(StoreNoteRequest $request): JsonResponse
    {
        $count = $this->noteService->createBulk($request->validated());

        return response()->json([
            'message' => "Notes created for {$count} student(s).",
            'count'   => $count,
        ], 201);
    }
}
