<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Database\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class InitialiseTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant) {
                tenancy()->initialize($tenant);

                // Spatie's teams feature scopes permissions to a team (tenant) via a
                // team_foreign_key. Without this call, $user->can() always returns false
                // because Spatie doesn't know which team's permission rows to load.
                // In tests this is set in TestCase::setUp(); in HTTP requests it must be
                // set here after tenancy is initialised.
                app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            }
        }

        return $next($request);
    }
}
