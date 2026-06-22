<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Repositories\ClassRepository;

class ClassService
{
    /**
     * Inject the repository via constructor.
     *
     * Design pattern: the service depends on the repository abstraction, not on Eloquent
     * directly. All database interaction is delegated to ClassRepository. This keeps the
     * service focused on orchestration logic (what to do and in what order) rather than
     * query construction (how to fetch it).
     */
    public function __construct(private readonly ClassRepository $repository) {}

    /**
     * Return the paginated class list and the tenant-wide summary in a single call.
     *
     * The controller needs both pieces to build the response — the paginator feeds the
     * ClassListCollection, and the summary is injected into the meta block. Returning
     * them together means the controller makes one service call, not two.
     */
    public function list(array $filters): array
    {
        return [
            'paginator' => $this->repository->list($filters),
            'summary'   => $this->repository->summary(),
        ];
    }

    /**
     * Load a single class with all relations for the detail view.
     *
     * Thin delegation — the service exists here so that the controller never calls the
     * repository directly. If additional logic were ever needed (e.g. permission logging,
     * caching) it would go here without touching the controller or repository.
     */
    public function find(int $id): SchoolClass
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Create a class and sync its staff and student assignments.
     *
     * The class record itself is created first, then the pivot tables are synced.
     * Both sync calls are safe on empty/null arrays — sync([]) removes all entries,
     * which for a new record is a no-op. The null coalescing ensures missing keys
     * from the request don't cause errors.
     *
     * created_by_user_id is not passed here — ClassObserver sets it automatically
     * on the Eloquent 'creating' event before the INSERT runs.
     */
    public function create(array $data): void
    {
        $class = $this->repository->create($data);

        $this->repository->syncUsers($class, $data['user_ids'] ?? []);
        $this->repository->syncStudents($class, $data['student_ids'] ?? []);
    }

    /**
     * Update a class and sync its staff and student assignments, then return the refreshed model.
     *
     * sync() on update handles all three cases in one call:
     *   - IDs in the new list but not the old → inserted into the pivot table
     *   - IDs in the old list but not the new → deleted from the pivot table
     *   - IDs in both → untouched
     *
     * The repository's update() returns a fresh() model with relations reloaded, so
     * the returned ClassDetailResource reflects the post-update state.
     */
    public function update(SchoolClass $class, array $data): SchoolClass
    {
        $updated = $this->repository->update($class, $data);

        $this->repository->syncUsers($updated, $data['user_ids'] ?? []);
        $this->repository->syncStudents($updated, $data['student_ids'] ?? []);

        return $updated;
    }

    /**
     * Soft-delete a class.
     */
    public function delete(SchoolClass $class): void
    {
        $this->repository->delete($class);
    }

}
