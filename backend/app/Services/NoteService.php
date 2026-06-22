<?php

namespace App\Services;

use App\Repositories\NoteRepository;
use Illuminate\Database\Eloquent\Collection;

class NoteService
{
    public function __construct(private readonly NoteRepository $noteRepository) {}

    // Delegate the notes query to the repository. The optional class_id filter is
    // passed through so the repository can scope the query to a specific class.
    public function forStudent(int $studentId, ?int $classId = null): Collection
    {
        return $this->noteRepository->forStudent($studentId, $classId);
    }

    // Bulk creation: iterate over every student_id and create one StudentNote per student.
    // The note_text, note_date, class_id, and confidentiality_level are identical across
    // all records — only student_id varies. Returns the count so the controller can build
    // the "Notes created for N student(s)." message without needing the actual records.
    public function createBulk(array $data): int
    {
        foreach ($data['student_ids'] as $studentId) {
            $this->noteRepository->create($data, $studentId);
        }

        return count($data['student_ids']);
    }
}
