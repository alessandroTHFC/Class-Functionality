<?php

namespace App\Policies;

use App\Models\User;

class StudentNotePolicy
{
    // Any role with 'view student notes' permission can list notes.
    public function viewAny(User $user): bool
    {
        return $user->can('view student notes');
    }

    // Only roles with 'add student note' permission can create notes.
    // read-only cannot create. teachers-assistant can (they have this permission).
    public function create(User $user): bool
    {
        return $user->can('add student note');
    }
}
