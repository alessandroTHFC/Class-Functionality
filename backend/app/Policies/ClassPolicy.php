<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

class ClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view classes');
    }

    public function view(User $user, SchoolClass $class): bool
    {
        return $user->can('view classes');
    }

    public function create(User $user): bool
    {
        return $user->can('create class');
    }

    public function update(User $user, SchoolClass $class): bool
    {
        return $user->can('edit class');
    }

    public function delete(User $user, SchoolClass $class): bool
    {
        return $user->can('delete class');
    }
}
