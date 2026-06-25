<?php

namespace App\Policies;

use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can list students.
     *
     * All five roles are granted the 'view students' permission in RolesAndPermissionsSeeder,
     * so in practice every authenticated user passes this check. The policy still exists so
     * future permission changes only require updating the seeder — no controller code changes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view students');
    }
}
