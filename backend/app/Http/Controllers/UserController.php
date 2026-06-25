<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * Return all staff users for the current tenant, ordered alphabetically.
     *
     * Used by the class dashboard staff filter dropdown and the class form dialog
     * staff assignment checklist. No pagination needed — each tenant has a small,
     * fixed set of staff users created by the seeder.
     *
     * Authorization reuses ClassPolicy::viewAny, which checks the 'view classes'
     * permission. All five roles hold this permission, so every authenticated user
     * can load the staff list. This matches the intent — any user who can see the
     * dashboard can also see who the staff members are.
     *
     * BelongsToTenant on User scopes the query to the active tenant automatically,
     * so staff from other tenants are never returned regardless of what is requested.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Models\SchoolClass::class);

        $users = User::orderBy('name')->get();

        return UserResource::collection($users);
    }
}
