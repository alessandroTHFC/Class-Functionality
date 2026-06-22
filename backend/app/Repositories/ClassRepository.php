<?php

namespace App\Repositories;

use App\Models\ClassStudent;
use App\Models\ClassUser;
use App\Models\SchoolClass;
use Illuminate\Pagination\LengthAwarePaginator;

class ClassRepository
{
    /**
     * Return a paginated list of classes for the current tenant, with optional filters.
     *
     * Design pattern: the repository owns all Eloquent queries. The service calls this method
     * and passes the raw filter array — no query logic leaks into the service or controller.
     *
     * Laravel concepts:
     * - with() eager-loads related models in a single query per relation (avoids N+1).
     * - withCount() adds a virtual `students_count` attribute to each model without loading
     *   the full student records — efficient for a list view that only needs the count.
     * - ->search() and ->whereHas() are called as query builder methods. search() is a local
     *   query scope defined on SchoolClass (scopeSearch strips the "scope" prefix).
     * - paginate() returns a LengthAwarePaginator which Laravel Resources automatically
     *   serialise into a data array + meta/links pagination block.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = SchoolClass::with(['yearLevel', 'createdBy', 'users'])
            ->withCount('students');

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['year_level_id'])) {
            $query->where('year_level_id', $filters['year_level_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $filters['user_id']));
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Return tenant-wide aggregate stats for the dashboard summary card.
     *
     * These run on unfiltered data — the summary always reflects the whole tenant,
     * regardless of what search/filter the user has applied to the class list.
     *
     * Laravel concepts:
     * - SchoolClass::select('id') builds a subquery. Passing it to whereIn() lets the
     *   database resolve the class IDs in one round-trip rather than loading them into PHP.
     * - BelongsToTenant scopes SchoolClass to the current tenant automatically, so the
     *   subquery is already tenant-scoped — ClassStudent itself has no tenant_id column.
     * - distinct('student_id')->count('student_id') counts unique students across all classes
     *   (a student enrolled in two classes counts once, not twice).
     */
    public function summary(): array
    {
        $classIds = SchoolClass::select('id');

        return [
            'total_students'    => ClassStudent::whereIn('class_id', $classIds)
                ->distinct('student_id')
                ->count('student_id'),
            'teachers_assigned' => ClassUser::whereIn('class_id', $classIds)
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    /**
     * Load a single class with all relations needed for the detail view.
     *
     * Loads students with their yearLevel nested (students.yearLevel uses dot notation
     * to eager-load a relationship of a relationship in one call).
     * findOrFail() throws a ModelNotFoundException if the ID doesn't exist, which Laravel
     * automatically converts to a 404 response.
     */
    public function findWithRelations(int $id): SchoolClass
    {
        return SchoolClass::with(['yearLevel', 'createdBy', 'users', 'students.yearLevel'])
            ->findOrFail($id);
    }

    /**
     * Persist a new class record.
     *
     * created_by_user_id is NOT set here — it is set by ClassObserver::creating() which
     * fires automatically before any SchoolClass::create() call. The observer reads
     * Auth::id() so this repository stays free of auth logic.
     *
     * tenant_id is also not set here — BelongsToTenant sets it via an Eloquent 'creating'
     * event listener from the active tenancy context.
     */
    public function create(array $data): SchoolClass
    {
        return SchoolClass::create([
            'name'          => $data['name'],
            'year_level_id' => $data['year_level_id'] ?? null,
        ]);
    }

    /**
     * Sync the full list of assigned staff for a class.
     *
     * sync() is the correct method for a full-state update: it adds IDs that are missing,
     * removes IDs that are no longer in the array, and leaves existing ones untouched.
     * This matches the frontend pattern of always submitting the full desired selection.
     * Kept separate from create/update so the service can call it independently.
     */
    public function syncUsers(SchoolClass $class, array $userIds): void
    {
        $class->users()->sync($userIds);
    }

    /**
     * Sync the full list of enrolled students for a class.
     *
     * Same sync() pattern as syncUsers(). An empty array removes all students from the class.
     */
    public function syncStudents(SchoolClass $class, array $studentIds): void
    {
        $class->students()->sync($studentIds);
    }

    /**
     * Update a class's own fields and return a freshly loaded instance.
     *
     * fresh() re-fetches the model from the database with the specified relations loaded.
     * This is used instead of returning $class directly because the update() call changes
     * attributes in place but does not reload relations — the controller needs the full
     * shape for ClassDetailResource, so we return a clean, freshly loaded model.
     */
    public function update(SchoolClass $class, array $data): SchoolClass
    {
        $class->update([
            'name'          => $data['name'],
            'year_level_id' => $data['year_level_id'] ?? null,
        ]);

        return $class->fresh(['yearLevel', 'createdBy', 'users', 'students.yearLevel']);
    }

    /**
     * Soft-delete a class.
     *
     * Because SchoolClass uses the SoftDeletes trait, delete() sets deleted_at rather than
     * removing the row. The record is excluded from all future queries automatically by the
     * global soft-delete scope Eloquent applies.
     */
    public function delete(SchoolClass $class): void
    {
        $class->delete();
    }

}
