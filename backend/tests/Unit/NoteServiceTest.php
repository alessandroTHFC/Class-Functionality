<?php

use App\Models\StudentNote;
use App\Repositories\NoteRepository;
use App\Services\NoteService;
use Illuminate\Database\Eloquent\Collection;

// Unit tests mock the repository so no database is touched — they verify NoteService's
// logic in isolation. Mockery (bundled with Pest) creates a mock that records and
// asserts method calls without running real Eloquent queries.

describe('NoteService::forStudent()', function () {

    // Verifies the service delegates to the repository and returns whatever the
    // repository returns. shouldReceive() sets an expectation on the mock —
    // if forStudent() is not called the test fails.
    it('delegates to the repository and returns the result', function () {
        $notes      = new Collection([new StudentNote()]);
        $repository = Mockery::mock(NoteRepository::class);

        // Expect forStudent(1, null) to be called exactly once and return our collection.
        $repository->shouldReceive('forStudent')
            ->once()
            ->with(1, null)
            ->andReturn($notes);

        $service = new NoteService($repository);
        $result  = $service->forStudent(1, null);

        expect($result)->toBe($notes);
    });

    it('passes class_id through to the repository when provided', function () {
        $repository = Mockery::mock(NoteRepository::class);

        $repository->shouldReceive('forStudent')
            ->once()
            ->with(5, 3)
            ->andReturn(new Collection());

        $service = new NoteService($repository);
        $service->forStudent(5, 3);
    });
});

describe('NoteService::createBulk()', function () {

    // Verifies that createBulk() calls NoteRepository::create() once per student_id.
    // times(3) asserts the method is called exactly 3 times — if the loop is broken
    // (e.g. only iterates once), this assertion catches it.
    it('calls repository create once per student_id', function () {
        $data = [
            'student_ids'           => [10, 11, 12],
            'class_id'              => 1,
            'note_text'             => 'Bulk note.',
            'note_date'             => '2026-06-16',
            'confidentiality_level' => null,
        ];

        $repository = Mockery::mock(NoteRepository::class);

        $repository->shouldReceive('create')
            ->times(3)
            ->andReturn(new StudentNote());

        $service = new NoteService($repository);
        $count   = $service->createBulk($data);

        // Returns the count so the controller can build the response message.
        expect($count)->toBe(3);
    });

    it('returns 1 when only one student_id is provided', function () {
        $data = [
            'student_ids' => [7],
            'class_id'    => 2,
            'note_text'   => 'Single note.',
            'note_date'   => '2026-06-16',
        ];

        $repository = Mockery::mock(NoteRepository::class);
        $repository->shouldReceive('create')->once()->andReturn(new StudentNote());

        $service = new NoteService($repository);
        $count   = $service->createBulk($data);

        expect($count)->toBe(1);
    });
});
