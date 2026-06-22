<?php

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;

// actingAsRole() creates a user with the given role and authenticates via Sanctum.
// BelongsToTenant on Student and StudentNote scopes all queries to the current tenant.

describe('GET /api/students/{student}/notes', function () {

    // assertJsonCount() counts the items in the data array — verifies the correct number
    // of notes are returned without asserting on specific field values.
    it('returns notes for a student', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        StudentNote::factory()->count(3)->create([
            'student_id' => $student->id,
            'tenant_id'  => test()->tenant->id,
        ]);

        actingAsRole('teacher')
            ->getJson("/api/students/{$student->id}/notes")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    // The optional class_id query parameter filters notes to a specific class.
    // NoteRepository::forStudent() applies a when() clause — only notes with a
    // matching class_id are returned.
    it('filters notes by class_id when provided', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $class   = SchoolClass::factory()->create();

        StudentNote::factory()->create(['student_id' => $student->id, 'class_id' => $class->id, 'tenant_id' => test()->tenant->id]);
        StudentNote::factory()->create(['student_id' => $student->id, 'tenant_id' => test()->tenant->id]); // different class

        actingAsRole('teacher')
            ->getJson("/api/students/{$student->id}/notes?class_id={$class->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    // assertJsonStructure() confirms the resource shape — checks keys exist, not their values.
    it('returns the correct response shape', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $author  = User::factory()->create(['tenant_id' => test()->tenant->id]);
        $class   = SchoolClass::factory()->create();

        StudentNote::factory()->create([
            'student_id' => $student->id,
            'user_id'    => $author->id,
            'class_id'   => $class->id,
            'tenant_id'  => test()->tenant->id,
        ]);

        actingAsRole('teacher')
            ->getJson("/api/students/{$student->id}/notes")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'note_text', 'note_date', 'confidentiality_level', 'author', 'class', 'created_at']],
            ]);
    });

    // read-only can view notes — StudentNotePolicy::viewAny() checks 'view student notes'
    // which all roles including read-only possess.
    it('returns notes for read-only user', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        actingAsRole('read-only')
            ->getJson("/api/students/{$student->id}/notes")
            ->assertOk();
    });

    it('returns 401 for unauthenticated request', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);

        $this->getJson("/api/students/{$student->id}/notes")->assertUnauthorized();
    });
});

describe('POST /api/notes', function () {

    // assertDatabaseCount() confirms exactly the right number of rows were written —
    // one per student_id in the bulk request.
    it('creates one note per student_id for a teacher', function () {
        $students = Student::factory()->count(3)->create(['tenant_id' => test()->tenant->id]);
        $class    = SchoolClass::factory()->create();

        actingAsRole('teacher')
            ->postJson('/api/notes', [
                'student_ids' => $students->pluck('id')->toArray(),
                'class_id'    => $class->id,
                'note_text'   => 'Great lesson today.',
                'note_date'   => '2026-06-16',
            ])
            ->assertCreated()
            ->assertJson(['message' => 'Notes created for 3 student(s).', 'count' => 3]);

        $this->assertDatabaseCount('student_notes', 3);
    });

    // teachers-assistant has 'add student note' permission — they can write notes
    // even though they cannot edit classes.
    it('allows teachers-assistant to create notes', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $class   = SchoolClass::factory()->create();

        actingAsRole('teachers-assistant')
            ->postJson('/api/notes', [
                'student_ids' => [$student->id],
                'class_id'    => $class->id,
                'note_text'   => 'Observed good participation.',
                'note_date'   => '2026-06-16',
            ])
            ->assertCreated();
    });

    // read-only lacks 'add student note' — StoreNoteRequest::authorize() returns false → 403.
    it('returns 403 when read-only tries to create a note', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $class   = SchoolClass::factory()->create();

        actingAsRole('read-only')
            ->postJson('/api/notes', [
                'student_ids' => [$student->id],
                'class_id'    => $class->id,
                'note_text'   => 'Should not work.',
                'note_date'   => '2026-06-16',
            ])
            ->assertForbidden();
    });

    // Validation runs after authorization — student_ids is required and must have at least 1 entry.
    it('returns 422 when student_ids is missing', function () {
        $class = SchoolClass::factory()->create();

        actingAsRole('teacher')
            ->postJson('/api/notes', [
                'class_id'  => $class->id,
                'note_text' => 'Missing students.',
                'note_date' => '2026-06-16',
            ])
            ->assertUnprocessable();
    });

    // Verifies NoteRepository::create() writes user_id from Auth::id().
    // actingAsRole() does not expose the created user, so we create the user manually
    // here so we can reference their ID in the assertion.
    it('sets user_id (author) from the authenticated user', function () {
        $student = Student::factory()->create(['tenant_id' => test()->tenant->id]);
        $class   = SchoolClass::factory()->create();

        $author = User::factory()->create(['tenant_id' => test()->tenant->id]);
        $author->assignRole('teacher');

        test()->actingAs($author, 'sanctum')
            ->postJson('/api/notes', [
                'student_ids' => [$student->id],
                'class_id'    => $class->id,
                'note_text'   => 'Test note.',
                'note_date'   => '2026-06-16',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('student_notes', [
            'student_id' => $student->id,
            'user_id'    => $author->id,
        ]);
    });
});
