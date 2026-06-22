<?php

namespace App\Repositories;

use App\Models\StudentNote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class NoteRepository
{
    // Fetch all notes for a student. If class_id is provided, filter to only notes
    // written for that class. Eager-loads author and schoolClass to avoid N+1 in
    // StudentNoteResource::toArray(). BelongsToTenant automatically scopes by tenant_id.
    public function forStudent(int $studentId, ?int $classId = null): Collection
    {
        return StudentNote::with(['author', 'schoolClass'])
            ->where('student_id', $studentId)
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->latest()
            ->get();
    }

    // Create a single StudentNote row. The caller (NoteService) loops over student_ids
    // and calls this once per student — each student gets its own record with identical
    // content, which is how "bulk creation" is implemented without a many-to-many table.
    // user_id is set here (not in the model or observer) because NoteRepository is
    // always called from an authenticated HTTP request context.
    public function create(array $data, int $studentId): StudentNote
    {
        return StudentNote::create([
            'student_id'            => $studentId,
            'class_id'              => $data['class_id'],
            'user_id'               => Auth::id(),
            'note_text'             => $data['note_text'],
            'note_date'             => $data['note_date'],
            'confidentiality_level' => $data['confidentiality_level'] ?? null,
        ]);
    }
}
