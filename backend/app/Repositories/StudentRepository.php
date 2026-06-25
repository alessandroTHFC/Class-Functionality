<?php

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentRepository
{
    /**
     * Return a paginated list of students for the current tenant.
     *
     * Design pattern: the repository owns all Eloquent queries. Student uses BelongsToTenant,
     * so the global scope restricts the query to the active tenant automatically — no manual
     * tenant_id filter is needed here.
     *
     * Ordering: family_name then given_name so the table displays in natural alphabetical order
     * (e.g. "Brown, Alice" before "Brown, Charlie" before "Clarke, David").
     *
     * Search: matches against either name field using OR so a user can type either a first
     * or last name and get results.
     *
     * per_page defaults to 100 so the class form dialog retrieves all students (~30 per tenant
     * in the seeded dataset) in a single request for client-side filtering and pagination.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Student::with('yearLevel')
            ->orderBy('family_name')
            ->orderBy('given_name');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('given_name', 'like', "%{$search}%")
                    ->orWhere('family_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['year_level_id'])) {
            $query->where('year_level_id', $filters['year_level_id']);
        }

        return $query->paginate($filters['per_page'] ?? 100);
    }
}
